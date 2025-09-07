<?php

namespace App\Service;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\Notification;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class NotificationService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function createMessageNotification(Message $message): void
    {
        $conversation = $message->getConversation();
        $sender = $message->getSender();

        foreach ($conversation->getParticipants() as $participant) {
            // Ne pas créer de notification pour l'expéditeur
            if ($participant === $sender) {
                continue;
            }

            $notification = new Notification();
            $notification->setTitle('Nouveau message');
            $notification->setContent($this->truncateContent($message->getContent()));
            $notification->setType('message');
            $notification->setUser($participant);
            $notification->setConversation($conversation);
            $notification->setData([
                'conversationId' => $conversation->getId(),
                'messageId' => $message->getId(),
                'senderName' => $sender->getName() . ' ' . $sender->getLastname()
            ]);

            $this->entityManager->persist($notification);
        }

        $this->entityManager->flush();
    }

    public function createConversationNotification(Conversation $conversation, User $creator): void
    {
        foreach ($conversation->getParticipants() as $participant) {
            // Ne pas créer de notification pour le créateur
            if ($participant === $creator) {
                continue;
            }

            $notification = new Notification();
            $notification->setTitle('Nouvelle conversation');
            $notification->setContent('Vous avez été ajouté à une nouvelle conversation');
            $notification->setType('conversation');
            $notification->setUser($participant);
            $notification->setConversation($conversation);
            $notification->setData([
                'conversationId' => $conversation->getId(),
                'creatorName' => $creator->getName() . ' ' . $creator->getLastname()
            ]);

            $this->entityManager->persist($notification);
        }

        $this->entityManager->flush();
    }

    private function truncateContent(string $content, int $maxLength = 100): string
    {
        if (strlen($content) <= $maxLength) {
            return $content;
        }

        return substr($content, 0, $maxLength) . '...';
    }
}
