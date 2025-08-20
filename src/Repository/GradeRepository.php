<?php
namespace App\Repository;

use App\Entity\Grade;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class GradeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Grade::class);
    }

    /**
     * @return Grade[]
     */
    public function findBySemesterId(int $semesterId): array
    {
        return $this->createQueryBuilder('g')
            ->innerJoin('g.course', 'c')
            ->innerJoin('c.courseUnit', 'cu')
            ->innerJoin('cu.semester', 's')
            ->andWhere('s.id = :sid')
            ->setParameter('sid', $semesterId)
            ->getQuery()
            ->getResult();
    }
}
