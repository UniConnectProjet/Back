<?php
namespace App\Repository;

use App\Entity\Grade;
use App\Entity\Professor;
use App\Entity\Course;
use App\Entity\Classe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

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

    /**
     * Récupère les notes d'un professeur pour un cours et une classe spécifiques
     * 
     * @return Grade[]
     */
    public function findGradesByProfessorCourseAndClass(
        Professor $professor,
        Course $course,
        Classe $class,
        ?\DateTimeInterface $from = null,
        ?\DateTimeInterface $to = null
    ): array {
        $qb = $this->createQueryBuilder('g')
            ->innerJoin('g.course', 'c')
            ->innerJoin('g.student', 's')
            ->innerJoin('s.classe', 'cl')
            ->innerJoin('c.professors', 'p')
            ->where('p.id = :professorId')
            ->andWhere('c.id = :courseId')
            ->andWhere('cl.id = :classId')
            ->setParameter('professorId', $professor->getId())
            ->setParameter('courseId', $course->getId())
            ->setParameter('classId', $class->getId())
            ->orderBy('g.createdAt', 'DESC');

        if ($from) {
            $qb->andWhere('g.createdAt >= :from')
               ->setParameter('from', $from);
        }

        if ($to) {
            $qb->andWhere('g.createdAt <= :to')
               ->setParameter('to', $to);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère toutes les notes d'un professeur pour une période donnée
     * 
     * @return Grade[]
     */
    public function findGradesByProfessorAndDateRange(
        Professor $professor,
        ?\DateTimeInterface $from = null,
        ?\DateTimeInterface $to = null
    ): array {
        $qb = $this->createQueryBuilder('g')
            ->innerJoin('g.course', 'c')
            ->innerJoin('c.professors', 'p')
            ->where('p.id = :professorId')
            ->setParameter('professorId', $professor->getId())
            ->orderBy('g.createdAt', 'DESC');

        if ($from) {
            $qb->andWhere('g.createdAt >= :from')
               ->setParameter('from', $from);
        }

        if ($to) {
            $qb->andWhere('g.createdAt <= :to')
               ->setParameter('to', $to);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère les notes d'un professeur pour tous ses cours
     * 
     * @return Grade[]
     */
    public function findGradesByProfessor(Professor $professor): array
    {
        return $this->createQueryBuilder('g')
            ->innerJoin('g.course', 'c')
            ->innerJoin('c.professors', 'p')
            ->where('p.id = :professorId')
            ->setParameter('professorId', $professor->getId())
            ->orderBy('g.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
