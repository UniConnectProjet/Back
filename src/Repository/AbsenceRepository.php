<?php

namespace App\Repository;

use App\Entity\Absence;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Absence>
 */
class AbsenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Absence::class);
    }

    public function findAllForStudentWithSemester(int $studentId): array
    {
        return $this->createQueryBuilder('a')
            ->addSelect('s')
            ->leftJoin('a.semester', 's')
            ->andWhere('a.student = :sid')
            ->setParameter('sid', $studentId)
            ->orderBy('s.startDate', 'ASC')
            ->addOrderBy('a.date', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
