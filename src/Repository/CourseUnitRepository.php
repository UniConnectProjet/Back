<?php

namespace App\Repository;

use App\Entity\CourseUnit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CourseUnitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CourseUnit::class);
    }

    /**
     * Retourne les UE d'un étudiant en passant par ses notes.
     *
     * @return CourseUnit[]
     */
    public function findByStudent(int $studentId): array
    {
        return $this->createQueryBuilder('cu')      // <-- root alias = cu
            ->distinct()
            ->innerJoin('cu.courses', 'c')          // CourseUnit -> courses
            ->innerJoin('c.students', 's')          // Course -> students (ManyToMany inversé)
            ->andWhere('s.id = :sid')
            ->setParameter('sid', $studentId)
            ->getQuery()
            ->getResult();
    }
}

