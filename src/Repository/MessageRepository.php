<?php

namespace App\Repository;

use App\Entity\Message;
use App\Entity\Conversation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Message>
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    /**
     * @return Message[]
     */
    public function findByConversation(Conversation $conversation, int $page = 1, int $limit = 50): array
    {
        $offset = ($page - 1) * $limit;

        return $this->createQueryBuilder('m')
            ->where('m.conversation = :conversation')
            ->setParameter('conversation', $conversation)
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function countByConversation(Conversation $conversation): int
    {
        return $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.conversation = :conversation')
            ->setParameter('conversation', $conversation)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return Message[]
     */
    public function findUnreadMessagesByConversation(Conversation $conversation): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.conversation = :conversation')
            ->andWhere('m.isRead = false')
            ->setParameter('conversation', $conversation)
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
