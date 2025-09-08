<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/notifications', name: 'api_notifications_')]
#[IsGranted('ROLE_USER')]
class NotificationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private NotificationRepository $notificationRepository
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        
        $page = (int) $request->query->get('page', 1);
        $limit = min((int) $request->query->get('limit', 20), 100);
        
        $notifications = $this->notificationRepository->findByUser($user, $page, $limit);
        $unreadCount = $this->notificationRepository->countUnreadByUser($user);

        return $this->json([
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'pagination' => [
                'page' => $page,
                'limit' => $limit
            ]
        ], Response::HTTP_OK, [], ['groups' => ['getNotifications']]);
    }

    #[Route('/unread', name: 'unread', methods: ['GET'])]
    public function unread(): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        
        $notifications = $this->notificationRepository->findUnreadByUser($user);
        $count = count($notifications);

        return $this->json([
            'notifications' => $notifications,
            'count' => $count
        ], Response::HTTP_OK, [], ['groups' => ['getNotifications']]);
    }

    #[Route('/{id}/read', name: 'mark_read', methods: ['PUT'])]
    public function markAsRead(int $id): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        
        $notification = $this->notificationRepository->find($id);
        if (!$notification) {
            return $this->json(['error' => 'Notification introuvable'], Response::HTTP_NOT_FOUND);
        }

        if ($notification->getUser() !== $user) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $notification->setIsRead(true);
        $this->entityManager->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/read-all', name: 'mark_all_read', methods: ['PUT'])]
    public function markAllAsRead(): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        
        $unreadNotifications = $this->notificationRepository->findUnreadByUser($user);
        
        foreach ($unreadNotifications as $notification) {
            $notification->setIsRead(true);
        }
        
        $this->entityManager->flush();

        return $this->json(['success' => true, 'count' => count($unreadNotifications)]);
    }

    #[Route('/count', name: 'count', methods: ['GET'])]
    public function count(): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        
        $count = $this->notificationRepository->countUnreadByUser($user);

        return $this->json(['count' => $count]);
    }
}
