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
    public function findByStudentClassBetween(Student $student, DateTimeInterface $from, DateTimeInterface $to): array
    {
        // 1) Récupération de la classe depuis Student (getClass|getGroup|getPromo)
        $class = null;
        foreach (['getClass', 'getGroup', 'getPromo', 'getClasse'] as $m) {
            if (\method_exists($student, $m)) {
                $class = $student->{$m}();
                if ($class) break;
            }
        }
        if (!$class) {
            return [];
        }

        $em = $this->getEntityManager();

        $courseMeta = $em->getClassMetadata(Course::class);
        $candAssoc = null;
        foreach (['class', 'group', 'promo', 'classe'] as $cand) {
            if ($courseMeta->hasAssociation($cand)) {
                $candAssoc = $cand;
                break;
            }
        }

        if ($candAssoc) {
            $qb = $this->createQueryBuilder('s')
                ->innerJoin('s.course', 'c')
                ->andWhere('c.' . $candAssoc . ' = :class')
                ->andWhere('s.startAt >= :from')
                ->andWhere('s.endAt   <= :to')
                ->setParameter('class', $class)
                ->setParameter('from',  $from)
                ->setParameter('to',    $to)
                ->orderBy('s.startAt', 'ASC');

            return $qb->getQuery()->getResult();
        }

        
        $sessionMeta = $em->getClassMetadata(CourseSession::class);
        foreach (['class', 'group', 'promo', 'classe'] as $cand) {
            if ($sessionMeta->hasAssociation($cand)) {
                $qb = $this->createQueryBuilder('s')
                    ->andWhere('s.' . $cand . ' = :class')
                    ->andWhere('s.startAt >= :from')
                    ->andWhere('s.endAt   <= :to')
                    ->setParameter('class', $class)
                    ->setParameter('from',  $from)
                    ->setParameter('to',    $to)
                    ->orderBy('s.startAt', 'ASC');

                return $qb->getQuery()->getResult();
            }
        }

        return [];
    }
}
