<?php

namespace App\Controller;

use App\Entity\Absence;
use App\Entity\Professor;
use App\Repository\AbsenceRepository;
use App\Repository\CourseRepository;
use App\Repository\ClasseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/prof/attendance')]
#[IsGranted('ROLE_PROFESSOR')]
class ProfessorAttendanceController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AbsenceRepository $absenceRepository,
        private CourseRepository $courseRepository,
        private ClasseRepository $classeRepository
    ) {}

    #[Route('/stats', name: 'prof_attendance_stats', methods: ['GET'])]
    public function getAttendanceStats(Request $request): JsonResponse
    {
        try {
            $professor = $this->getUser()->getProfessor();
            if (!$professor) {
                return new JsonResponse(['error' => 'Professor not found'], 404);
            }

            $startDate = $request->query->get('startDate');
            $endDate = $request->query->get('endDate');
            $groupBy = $request->query->get('groupBy', 'week'); // week, month, semester
            $classId = $request->query->get('classId');
            $courseId = $request->query->get('courseId');

            // Validation des paramètres
            if (!$startDate || !$endDate) {
                return new JsonResponse(['error' => 'startDate and endDate are required'], 400);
            }

            if (!in_array($groupBy, ['week', 'month', 'semester'])) {
                return new JsonResponse(['error' => 'groupBy must be week, month, or semester'], 400);
            }

            try {
                $startDate = new \DateTime($startDate);
                $endDate = new \DateTime($endDate);
            } catch (\Exception $e) {
                return new JsonResponse(['error' => 'Invalid date format'], 400);
            }

            // Récupérer les absences avec filtres
            $absences = $this->absenceRepository->findAbsencesForProfessor(
                $professor,
                $startDate,
                $endDate,
                $classId,
                $courseId
            );

            // Grouper les données selon le groupBy
            $groupedData = $this->groupAbsencesByPeriod($absences, $groupBy, $startDate, $endDate);

            return new JsonResponse([
                'period' => [
                    'start' => $startDate->format('Y-m-d'),
                    'end' => $endDate->format('Y-m-d'),
                    'groupBy' => $groupBy
                ],
                'data' => $groupedData,
                'summary' => $this->calculateSummary($absences)
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Internal server error',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    #[Route('/by-class', name: 'prof_attendance_by_class', methods: ['GET'])]
    public function getAttendanceByClass(Request $request): JsonResponse
    {
        $professor = $this->getUser()->getProfessor();
        if (!$professor) {
            return new JsonResponse(['error' => 'Professor not found'], 404);
        }

        $startDate = $request->query->get('startDate');
        $endDate = $request->query->get('endDate');
        $courseId = $request->query->get('courseId');

        if (!$startDate || !$endDate) {
            return new JsonResponse(['error' => 'startDate and endDate are required'], 400);
        }

        try {
            $startDate = new \DateTime($startDate);
            $endDate = new \DateTime($endDate);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Invalid date format'], 400);
        }

        $classStats = $this->absenceRepository->findAttendanceStatsByClass(
            $professor,
            $startDate,
            $endDate,
            $courseId
        );

        return new JsonResponse([
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d')
            ],
            'classes' => $classStats
        ]);
    }

    #[Route('/top-absentees', name: 'prof_attendance_top_absentees', methods: ['GET'])]
    public function getTopAbsentees(Request $request): JsonResponse
    {
        $professor = $this->getUser()->getProfessor();
        if (!$professor) {
            return new JsonResponse(['error' => 'Professor not found'], 404);
        }

        $startDate = $request->query->get('startDate');
        $endDate = $request->query->get('endDate');
        $classId = $request->query->get('classId');
        $courseId = $request->query->get('courseId');
        $limit = (int) $request->query->get('limit', 10);

        if (!$startDate || !$endDate) {
            return new JsonResponse(['error' => 'startDate and endDate are required'], 400);
        }

        try {
            $startDate = new \DateTime($startDate);
            $endDate = new \DateTime($endDate);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Invalid date format'], 400);
        }

        $topAbsentees = $this->absenceRepository->findTopAbsentees(
            $professor,
            $startDate,
            $endDate,
            $classId,
            $courseId,
            $limit
        );

        return new JsonResponse([
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d')
            ],
            'topAbsentees' => $topAbsentees
        ]);
    }

    #[Route('/detail', name: 'prof_attendance_detail', methods: ['GET'])]
    public function getAttendanceDetail(Request $request): JsonResponse
    {
        $professor = $this->getUser()->getProfessor();
        if (!$professor) {
            return new JsonResponse(['error' => 'Professor not found'], 404);
        }

        $startDate = $request->query->get('startDate');
        $endDate = $request->query->get('endDate');
        $classId = $request->query->get('classId');
        $courseId = $request->query->get('courseId');

        if (!$startDate || !$endDate) {
            return new JsonResponse(['error' => 'startDate and endDate are required'], 400);
        }

        try {
            $startDate = new \DateTime($startDate);
            $endDate = new \DateTime($endDate);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Invalid date format'], 400);
        }

        $details = $this->absenceRepository->findAttendanceDetails(
            $professor,
            $startDate,
            $endDate,
            $classId,
            $courseId
        );

        return new JsonResponse([
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d')
            ],
            'details' => $details
        ]);
    }

    private function groupAbsencesByPeriod(array $absences, string $groupBy, \DateTime $startDate, \DateTime $endDate): array
    {
        $grouped = [];
        $current = clone $startDate;

        while ($current <= $endDate) {
            $periodKey = $this->getPeriodKey($current, $groupBy);
            $periodEnd = $this->getPeriodEnd($current, $groupBy);
            
            if ($periodEnd > $endDate) {
                $periodEnd = $endDate;
            }

            $periodAbsences = array_filter($absences, function($absence) use ($current, $periodEnd) {
                $absenceDate = $absence->getStartedDate();
                return $absenceDate >= $current && $absenceDate <= $periodEnd;
            });

            $grouped[] = [
                'period' => $periodKey,
                'startDate' => $current->format('Y-m-d'),
                'endDate' => $periodEnd->format('Y-m-d'),
                'totalAbsences' => count($periodAbsences),
                'justifiedAbsences' => count(array_filter($periodAbsences, fn($a) => $a->isJustified())),
                'unjustifiedAbsences' => count(array_filter($periodAbsences, fn($a) => !$a->isJustified())),
                'attendanceRate' => $this->calculateAttendanceRate($periodAbsences)
            ];

            $current = $this->getNextPeriod($current, $groupBy);
        }

        return $grouped;
    }

    private function getPeriodKey(\DateTime $date, string $groupBy): string
    {
        switch ($groupBy) {
            case 'week':
                return 'S' . $date->format('W') . ' ' . $date->format('Y');
            case 'month':
                return $date->format('F Y');
            case 'semester':
                $month = (int) $date->format('n');
                $year = $date->format('Y');
                return ($month <= 6) ? "S1 $year" : "S2 $year";
            default:
                return $date->format('Y-m-d');
        }
    }

    private function getPeriodEnd(\DateTime $date, string $groupBy): \DateTime
    {
        $end = clone $date;
        switch ($groupBy) {
            case 'week':
                $end->modify('+6 days');
                break;
            case 'month':
                $end->modify('last day of this month');
                break;
            case 'semester':
                $month = (int) $date->format('n');
                if ($month <= 6) {
                    $end->setDate($date->format('Y'), 6, 30);
                } else {
                    $end->setDate($date->format('Y'), 12, 31);
                }
                break;
        }
        return $end;
    }

    private function getNextPeriod(\DateTime $date, string $groupBy): \DateTime
    {
        $next = clone $date;
        switch ($groupBy) {
            case 'week':
                $next->modify('+1 week');
                break;
            case 'month':
                $next->modify('+1 month');
                break;
            case 'semester':
                $month = (int) $date->format('n');
                if ($month <= 6) {
                    $next->setDate($date->format('Y'), 7, 1);
                } else {
                    $next->setDate($date->format('Y') + 1, 1, 1);
                }
                break;
        }
        return $next;
    }

    private function calculateAttendanceRate(array $absences): float
    {
        if (empty($absences)) {
            return 100.0;
        }

        $totalSessions = count($absences);
        $absencesCount = count(array_filter($absences, fn($a) => $a->getPresenceStatus() === Absence::STATUS_ABSENT));
        
        return round((($totalSessions - $absencesCount) / $totalSessions) * 100, 2);
    }

    private function calculateSummary(array $absences): array
    {
        $totalAbsences = count($absences);
        $justifiedAbsences = count(array_filter($absences, fn($a) => $a->isJustified()));
        $unjustifiedAbsences = $totalAbsences - $justifiedAbsences;

        return [
            'totalAbsences' => $totalAbsences,
            'justifiedAbsences' => $justifiedAbsences,
            'unjustifiedAbsences' => $unjustifiedAbsences,
            'justificationRate' => $totalAbsences > 0 ? round(($justifiedAbsences / $totalAbsences) * 100, 2) : 0,
            'attendanceRate' => $this->calculateAttendanceRate($absences)
        ];
    }
}
