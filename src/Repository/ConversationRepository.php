<?php

namespace App\Repository;

use App\Entity\Conversation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Conversation>
 */
class ConversationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conversation::class);
    }

    /**
     * @return Conversation[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.participants', 'p')
            ->leftJoin('c.messages', 'm')
            ->addSelect('m')
            ->where('p = :user')
            ->setParameter('user', $user)
            ->orderBy('c.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findConversationBetweenUsers(User $user1, User $user2): ?Conversation
    {
        return $this->createQueryBuilder('c')
            ->join('c.participants', 'p1')
            ->join('c.participants', 'p2')
            ->where('p1 = :user1 AND p2 = :user2')
            ->setParameter('user1', $user1)
            ->setParameter('user2', $user2)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findConversationWithParticipants(array $participantIds): ?Conversation
    {
        $qb = $this->createQueryBuilder('c')
            ->join('c.participants', 'p')
            ->where('p.id IN (:participantIds)')
            ->setParameter('participantIds', $participantIds)
            ->groupBy('c.id')
            ->having('COUNT(DISTINCT p.id) = :count')
            ->setParameter('count', count($participantIds));

        return $qb->getQuery()->getOneOrNullResult();
    }
}
