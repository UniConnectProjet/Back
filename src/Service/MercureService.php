<?php

namespace App\Service;

use App\Entity\Conversation;
use App\Entity\Message;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class MercureService
{
    public function __construct(
        private HubInterface $hub
    ) {
    }

    public function publishMessage(Message $message): void
    {
        $conversation = $message->getConversation();
        $topic = "conversation/{$conversation->getId()}";
        
        $data = [
            'type' => 'message',
            'message' => [
                'id' => $message->getId(),
                'content' => $message->getContent(),
                'createdAt' => $message->getCreatedAt()->format('c'),
                'sender' => [
                    'id' => $message->getSender()->getId(),
                    'name' => $message->getSender()->getName(),
                    'lastname' => $message->getSender()->getLastname()
                ]
            ]
        ];

        $update = new Update($topic, json_encode($data));
        $this->hub->publish($update);
    }

    public function publishConversationUpdate(Conversation $conversation): void
    {
        $topic = "conversation/{$conversation->getId()}";
        
        $data = [
            'type' => 'conversation_update',
            'conversation' => [
                'id' => $conversation->getId(),
                'title' => $conversation->getTitle(),
                'updatedAt' => $conversation->getUpdatedAt()?->format('c'),
                'lastMessage' => $conversation->getLastMessage() ? [
                    'id' => $conversation->getLastMessage()->getId(),
                    'content' => $conversation->getLastMessage()->getContent(),
                    'createdAt' => $conversation->getLastMessage()->getCreatedAt()->format('c'),
                    'sender' => [
                        'id' => $conversation->getLastMessage()->getSender()->getId(),
                        'name' => $conversation->getLastMessage()->getSender()->getName(),
                        'lastname' => $conversation->getLastMessage()->getSender()->getLastname()
                    ]
                ] : null
            ]
        ];

        $update = new Update($topic, json_encode($data));
        $this->hub->publish($update);
    }

    public function publishNotification(string $userId, array $notificationData): void
    {
        $topic = "user/{$userId}/notifications";
        
        $data = [
            'type' => 'notification',
            'notification' => $notificationData
        ];

        $update = new Update($topic, json_encode($data));
        $this->hub->publish($update);
    }
}
