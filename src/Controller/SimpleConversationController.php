<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\User;
use App\Repository\ConversationRepository;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/conversations', name: 'api_conversations_')]
#[IsGranted('ROLE_USER')]
class SimpleConversationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
        private ConversationRepository $conversationRepository,
        private MessageRepository $messageRepository,
        private UserRepository $userRepository,
        private NotificationService $notificationService
        // private MercureService $mercureService // Temporairement désactivé
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Récupérer les conversations de l'utilisateur
        $conversations = $this->conversationRepository->findByUser($user);

        // Ajouter le compteur de messages non lus pour chaque conversation
        $conversationsWithUnreadCount = [];
        foreach ($conversations as $conversation) {
            $unreadCount = $conversation->getUnreadCountForUser($user);
            error_log("Conversation {$conversation->getId()}: unreadCount = {$unreadCount}");
            
            // Créer un tableau avec les données de la conversation + le compteur
            $conversationData = [
                'id' => $conversation->getId(),
                'title' => $conversation->getTitle(),
                'createdAt' => $conversation->getCreatedAt(),
                'updatedAt' => $conversation->getUpdatedAt(),
                'participants' => $conversation->getParticipants(),
                'messages' => $conversation->getMessages(),
                'lastMessage' => $conversation->getLastMessage(),
                'unreadCount' => $unreadCount
            ];
            
            $conversationsWithUnreadCount[] = $conversationData;
        }

        return $this->json($conversationsWithUnreadCount, Response::HTTP_OK, [], ['groups' => ['getConversations']]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $data = json_decode($request->getContent(), true);
        
        if (!$data || !isset($data['participantIds'])) {
            return $this->json(['error' => 'participantIds is required'], Response::HTTP_BAD_REQUEST);
        }

        // Créer une nouvelle conversation
        $conversation = new Conversation();
        $conversation->setTitle($data['title'] ?? 'Nouvelle conversation');
        $conversation->addParticipant($user);

        // Ajouter les autres participants
        foreach ($data['participantIds'] as $participantId) {
            $participant = $this->userRepository->find($participantId);
            if ($participant) {
                $conversation->addParticipant($participant);
            }
        }

        $this->entityManager->persist($conversation);
        $this->entityManager->flush();

        return $this->json($conversation, Response::HTTP_CREATED, [], ['groups' => ['getConversations']]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $conversation = $this->conversationRepository->find($id);
        
        if (!$conversation) {
            return $this->json(['error' => 'Conversation not found'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier que l'utilisateur est participant
        if (!$conversation->getParticipants()->contains($user)) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        return $this->json($conversation, Response::HTTP_OK, [], ['groups' => ['getConversations']]);
    }

    #[Route('/{id}/messages', name: 'messages', methods: ['GET'])]
    public function getMessages(int $id, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $conversation = $this->conversationRepository->find($id);
        
        if (!$conversation) {
            return $this->json(['error' => 'Conversation not found'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier que l'utilisateur est participant
        if (!$conversation->getParticipants()->contains($user)) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 50);
        
        $messages = $this->messageRepository->findByConversation($conversation, $page, $limit);

        return $this->json($messages, Response::HTTP_OK, [], ['groups' => ['getMessages']]);
    }

    #[Route('/{id}/messages/read', name: 'mark_messages_read', methods: ['PUT'])]
    public function markMessagesAsRead(int $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $conversation = $this->conversationRepository->find($id);
        if (!$conversation) {
            return $this->json(['error' => 'Conversation not found'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier que l'utilisateur est participant
        if (!$conversation->getParticipants()->contains($user)) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        // Marquer tous les messages non lus de cette conversation comme lus
        $unreadMessages = $this->messageRepository->findUnreadByConversationAndUser($conversation, $user);
        
        foreach ($unreadMessages as $message) {
            $message->setIsRead(true);
        }
        
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'markedCount' => count($unreadMessages)
        ]);
    }

    #[Route('/{id}/messages', name: 'send_message', methods: ['POST'])]
    public function sendMessage(int $id, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $conversation = $this->conversationRepository->find($id);
        
        if (!$conversation) {
            return $this->json(['error' => 'Conversation not found'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier que l'utilisateur est participant
        if (!$conversation->getParticipants()->contains($user)) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        
        if (!$data || !isset($data['content'])) {
            return $this->json(['error' => 'content is required'], Response::HTTP_BAD_REQUEST);
        }

        // Créer un nouveau message
        $message = new Message();
        $message->setContent($data['content']);
        $message->setSender($user);
        $message->setConversation($conversation);

        $this->entityManager->persist($message);
        $this->entityManager->flush();

        // Créer les notifications pour les autres participants
        $this->notificationService->createMessageNotification($message);

        // TODO: Réactiver Mercure une fois configuré correctement
        // Pour l'instant, les messages sont sauvegardés en base et récupérés au refresh

        return $this->json($message, Response::HTTP_CREATED, [], ['groups' => ['getMessages']]);
    }

    private function truncateContent(string $content, int $maxLength = 100): string
    {
        if (strlen($content) <= $maxLength) {
            return $content;
        }
        
        return substr($content, 0, $maxLength) . '...';
    }
}
