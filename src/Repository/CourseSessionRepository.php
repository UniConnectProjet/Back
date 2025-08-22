<?php

namespace App\Repository;

use App\Entity\CourseSession;
use App\Entity\Student;
use App\Entity\Course;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CourseSession>
 */
final class CourseSessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CourseSession::class);
    }

    /**
     * Retourne les sessions de la *classe du student* entre deux dates.
     * Détecte dynamiquement le nom de l'association (class/group/promo) sur Course
     * (ou à défaut sur CourseSession) pour éviter les divergences de schéma.
     *
     * @return CourseSession[]
     */
    public function findByClasseAndRange(Classe $classe, \DateTimeInterface $from, \DateTimeInterface $to): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.classe = :classe')
            ->andWhere('s.startAt >= :from AND s.startAt <= :to')
            ->setParameter('classe', $classe)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('s.startAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

}
