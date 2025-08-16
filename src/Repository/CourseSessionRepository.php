<?php

namespace App\Repository;

use App\Entity\Classe;
use App\Entity\CourseSession;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CourseSessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CourseSession::class);
    }

    /** @return CourseSession[] */
    public function findByClasseBetween(Classe $classe, \DateTimeInterface $from, \DateTimeInterface $to): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.classe = :classe')
            ->andWhere('s.startAt >= :from')
            ->andWhere('s.startAt < :to')
            ->setParameter('classe', $classe)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('s.startAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}