<?php

namespace App\Repository;

use App\Entity\Absence;
use App\Entity\Professor;
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
            ->addOrderBy('a.startedDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAbsencesForProfessor(
        Professor $professor,
        \DateTime $startDate,
        \DateTime $endDate,
        ?int $classId = null,
        ?int $courseId = null
    ): array {
        try {
            // Requête simplifiée pour éviter les erreurs de jointure
            $qb = $this->createQueryBuilder('a')
                ->leftJoin('a.student', 's')
                ->leftJoin('a.courseSession', 'cs')
                ->leftJoin('cs.course', 'c')
                ->where('a.startedDate >= :startDate')
                ->andWhere('a.startedDate <= :endDate')
                ->setParameter('startDate', $startDate)
                ->setParameter('endDate', $endDate);

            // Filtrer par professeur via les cours
            $qb->andWhere('c.id IN (
                SELECT c2.id FROM App\Entity\Course c2 
                JOIN c2.professors p2 
                WHERE p2.id = :professorId
            )')
            ->setParameter('professorId', $professor->getId());

            if ($classId) {
                $qb->leftJoin('s.classe', 'cl')
                   ->andWhere('cl.id = :classId')
                   ->setParameter('classId', $classId);
            }

            if ($courseId) {
                $qb->andWhere('c.id = :courseId')
                   ->setParameter('courseId', $courseId);
            }

            return $qb->orderBy('a.startedDate', 'ASC')
                      ->getQuery()
                      ->getResult();
        } catch (\Exception $e) {
            // En cas d'erreur, retourner un tableau vide plutôt que de planter
            error_log('Error in findAbsencesForProfessor: ' . $e->getMessage());
            return [];
        }
    }

    public function findAttendanceStatsByClass(
        Professor $professor,
        \DateTime $startDate,
        \DateTime $endDate,
        ?int $courseId = null
    ): array {
        $qb = $this->createQueryBuilder('a')
            ->select('
                cl.id as classId,
                cl.name as className,
                COUNT(a.id) as totalAbsences,
                SUM(CASE WHEN a.justified = true THEN 1 ELSE 0 END) as justifiedAbsences,
                SUM(CASE WHEN a.justified = false THEN 1 ELSE 0 END) as unjustifiedAbsences,
                COUNT(DISTINCT s.id) as totalStudents,
                COUNT(DISTINCT cs.id) as totalSessions
            ')
            ->leftJoin('a.student', 's')
            ->leftJoin('s.classe', 'cl')
            ->leftJoin('a.courseSession', 'cs')
            ->leftJoin('cs.course', 'c')
            ->leftJoin('c.professors', 'p')
            ->where('p.id = :professorId')
            ->andWhere('a.startedDate >= :startDate')
            ->andWhere('a.startedDate <= :endDate')
            ->setParameter('professorId', $professor->getId())
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate);

        if ($courseId) {
            $qb->andWhere('c.id = :courseId')
               ->setParameter('courseId', $courseId);
        }

        $results = $qb->groupBy('cl.id')
                      ->orderBy('totalAbsences', 'DESC')
                      ->getQuery()
                      ->getResult();

        // Calculer les taux de présence
        return array_map(function($row) {
            $attendanceRate = $row['totalSessions'] > 0 
                ? round((($row['totalSessions'] - $row['totalAbsences']) / $row['totalSessions']) * 100, 2)
                : 100.0;
            
            $justificationRate = $row['totalAbsences'] > 0
                ? round(($row['justifiedAbsences'] / $row['totalAbsences']) * 100, 2)
                : 0;

            return [
                'classId' => $row['classId'],
                'className' => $row['className'],
                'totalAbsences' => (int) $row['totalAbsences'],
                'justifiedAbsences' => (int) $row['justifiedAbsences'],
                'unjustifiedAbsences' => (int) $row['unjustifiedAbsences'],
                'totalStudents' => (int) $row['totalStudents'],
                'totalSessions' => (int) $row['totalSessions'],
                'attendanceRate' => $attendanceRate,
                'justificationRate' => $justificationRate
            ];
        }, $results);
    }

    public function findTopAbsentees(
        Professor $professor,
        \DateTime $startDate,
        \DateTime $endDate,
        ?int $classId = null,
        ?int $courseId = null,
        int $limit = 10
    ): array {
        $qb = $this->createQueryBuilder('a')
            ->select('
                s.id as studentId,
                u.name as studentName,
                u.lastname as studentLastname,
                cl.name as className,
                COUNT(a.id) as totalAbsences,
                SUM(CASE WHEN a.justified = true THEN 1 ELSE 0 END) as justifiedAbsences,
                SUM(CASE WHEN a.justified = false THEN 1 ELSE 0 END) as unjustifiedAbsences,
                MAX(a.startedDate) as lastAbsenceDate
            ')
            ->leftJoin('a.student', 's')
            ->leftJoin('s.user', 'u')
            ->leftJoin('s.classe', 'cl')
            ->leftJoin('a.courseSession', 'cs')
            ->leftJoin('cs.course', 'c')
            ->leftJoin('c.professors', 'p')
            ->where('p.id = :professorId')
            ->andWhere('a.startedDate >= :startDate')
            ->andWhere('a.startedDate <= :endDate')
            ->setParameter('professorId', $professor->getId())
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate);

        if ($classId) {
            $qb->andWhere('cl.id = :classId')
               ->setParameter('classId', $classId);
        }

        if ($courseId) {
            $qb->andWhere('c.id = :courseId')
               ->setParameter('courseId', $courseId);
        }

        $results = $qb->groupBy('s.id')
                      ->orderBy('totalAbsences', 'DESC')
                      ->setMaxResults($limit)
                      ->getQuery()
                      ->getResult();

        return array_map(function($row) {
            return [
                'studentId' => $row['studentId'],
                'studentName' => $row['studentName'],
                'studentLastname' => $row['studentLastname'],
                'className' => $row['className'],
                'totalAbsences' => (int) $row['totalAbsences'],
                'justifiedAbsences' => (int) $row['justifiedAbsences'],
                'unjustifiedAbsences' => (int) $row['unjustifiedAbsences'],
                'lastAbsenceDate' => $row['lastAbsenceDate'] ? $row['lastAbsenceDate']->format('Y-m-d') : null
            ];
        }, $results);
    }

    public function findAttendanceDetails(
        Professor $professor,
        \DateTime $startDate,
        \DateTime $endDate,
        ?int $classId = null,
        ?int $courseId = null
    ): array {
        $qb = $this->createQueryBuilder('a')
            ->select('
                a.id as absenceId,
                a.startedDate as absenceDate,
                a.justified as isJustified,
                a.justificationReason as justificationReason,
                s.id as studentId,
                u.name as studentName,
                u.lastname as studentLastname,
                cl.name as className,
                c.name as courseName,
                cs.startTime as sessionStartTime,
                cs.endTime as sessionEndTime
            ')
            ->leftJoin('a.student', 's')
            ->leftJoin('s.user', 'u')
            ->leftJoin('s.classe', 'cl')
            ->leftJoin('a.courseSession', 'cs')
            ->leftJoin('cs.course', 'c')
            ->leftJoin('c.professors', 'p')
            ->where('p.id = :professorId')
            ->andWhere('a.startedDate >= :startDate')
            ->andWhere('a.startedDate <= :endDate')
            ->setParameter('professorId', $professor->getId())
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate);

        if ($classId) {
            $qb->andWhere('cl.id = :classId')
               ->setParameter('classId', $classId);
        }

        if ($courseId) {
            $qb->andWhere('c.id = :courseId')
               ->setParameter('courseId', $courseId);
        }

        $results = $qb->orderBy('a.startedDate', 'DESC')
                      ->getQuery()
                      ->getResult();

        return array_map(function($row) {
            return [
                'absenceId' => $row['absenceId'],
                'absenceDate' => $row['absenceDate']->format('Y-m-d'),
                'isJustified' => (bool) $row['isJustified'],
                'justificationReason' => $row['justificationReason'],
                'studentId' => $row['studentId'],
                'studentName' => $row['studentName'],
                'studentLastname' => $row['studentLastname'],
                'className' => $row['className'],
                'courseName' => $row['courseName'],
                'sessionStartTime' => $row['sessionStartTime'] ? $row['sessionStartTime']->format('H:i') : null,
                'sessionEndTime' => $row['sessionEndTime'] ? $row['sessionEndTime']->format('H:i') : null
            ];
        }, $results);
    }
}
