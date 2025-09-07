<?php

namespace App\Service;

use App\Entity\Grade;
use App\Entity\Professor;
use App\Entity\Course;
use App\Entity\Classe;
use App\Entity\Semester;
use App\Repository\GradeRepository;
use App\Repository\SemesterRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\Collections\Collection;

class ProfessorGradeService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private GradeRepository $gradeRepository,
        private SemesterRepository $semesterRepository
    ) {}

    /**
     * Détermine le semestre basé sur la date de création
     * S1: 2 septembre - 21 janvier
     * S2: 2 février - 26 juin
     */
    public function determineSemesterFromDate(\DateTimeInterface $date): string
    {
        $year = $date->format('Y');
        $month = (int) $date->format('n');
        $day = (int) $date->format('j');

        // S1: du 2 septembre au 21 janvier
        if (($month === 9 && $day >= 2) || 
            $month === 10 || $month === 11 || $month === 12 || 
            ($month === 1 && $day <= 21)) {
            return 'S1';
        }
        
        // S2: du 2 février au 26 juin
        if (($month === 2 && $day >= 2) || 
            $month === 3 || $month === 4 || $month === 5 || 
            ($month === 6 && $day <= 26)) {
            return 'S2';
        }

        // Par défaut, considérer comme S1 si hors période
        return 'S1';
    }

    /**
     * Normalise une note sur 20
     */
    public function normalizeGrade(float $grade, float $divisor): float
    {
        if ($divisor === 0) {
            return 0.0;
        }
        
        return ($grade / $divisor) * 20;
    }

    /**
     * Calcule la moyenne d'un groupe de notes
     */
    public function calculateAverage(array $grades): float
    {
        if (empty($grades)) {
            return 0.0;
        }

        $sum = 0;
        $count = 0;

        foreach ($grades as $grade) {
            if ($grade['divisor'] > 0) {
                $normalizedGrade = $this->normalizeGrade($grade['grade'], $grade['divisor']);
                $sum += $normalizedGrade;
                $count++;
            }
        }

        return $count > 0 ? round($sum / $count, 1) : 0.0;
    }

    /**
     * Récupère les notes d'un professeur pour une période donnée
     */
    public function getProfessorGradesOverview(Professor $professor, ?\DateTimeInterface $from = null, ?\DateTimeInterface $to = null): array
    {
        // Si pas de dates spécifiées, utiliser l'année universitaire courante
        if (!$from || !$to) {
            $currentYear = date('Y');
            $from = new \DateTime("{$currentYear}-09-02");
            $to = new \DateTime("{$currentYear}-06-26");
        }

        // Récupérer les cours du professeur
        $courses = $professor->getCourses();
        
        $result = [
            'professor' => [
                'id' => $professor->getId(),
                'fullName' => $professor->getUserId() ? 
                    $professor->getUserId()->getName() . ' ' . $professor->getUserId()->getLastname() : 
                    'Professeur inconnu'
            ],
            'range' => [
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d')
            ],
            'byCourse' => []
        ];

        $courseAverages = [];

        foreach ($courses as $course) {
            $courseData = [
                'courseId' => $course->getId(),
                'courseTitle' => $course->getName(),
                'classes' => []
            ];

            $classAverages = [];

            foreach ($course->getClasses() as $class) {
                $classData = [
                    'classId' => $class->getId(),
                    'classLabel' => $class->getName(),
                    'controls' => [],
                    'classAverage' => 0.0
                ];

                // Récupérer les notes pour cette classe et ce cours
                $grades = $this->gradeRepository->findGradesByProfessorCourseAndClass(
                    $professor,
                    $course,
                    $class,
                    $from,
                    $to
                );

                // Grouper les notes par contrôle (title + date de création)
                $controls = $this->groupGradesByControl($grades);

                foreach ($controls as $control) {
                    $controlData = [
                        'title' => $control['title'],
                        'createdAt' => $control['createdAt']->format('c'),
                        'semester' => $this->determineSemesterFromDate($control['createdAt']),
                        'divisor' => $control['divisor'],
                        'average' => $control['average'],
                        'count' => $control['count'],
                        'grades' => $control['grades']
                    ];

                    $classData['controls'][] = $controlData;
                    $classAverages[] = $control['average'];
                }

                // Calculer la moyenne de la classe
                $classData['classAverage'] = $this->calculateAverage(
                    array_map(fn($control) => [
                        'grade' => $control['average'],
                        'divisor' => 20
                    ], $controls)
                );

                $courseData['classes'][] = $classData;
                $classAverages[] = $classData['classAverage'];
            }

            // Calculer la moyenne du cours
            $courseData['courseAverage'] = $this->calculateAverage(
                array_map(fn($avg) => ['grade' => $avg, 'divisor' => 20], $classAverages)
            );

            $result['byCourse'][] = $courseData;
            $courseAverages[] = $courseData['courseAverage'];
        }

        // Calculer la moyenne globale
        $result['courseAverageGlobal'] = $this->calculateAverage(
            array_map(fn($avg) => ['grade' => $avg, 'divisor' => 20], $courseAverages)
        );

        return $result;
    }

    /**
     * Groupe les notes par contrôle (title + date de création)
     */
    private function groupGradesByControl(array $grades): array
    {
        $grouped = [];

        foreach ($grades as $grade) {
            $key = $grade->getTitle() . '_' . $grade->getCreatedAt()->format('Y-m-d');
            
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'title' => $grade->getTitle(),
                    'createdAt' => $grade->getCreatedAt(),
                    'divisor' => $grade->getDividor(),
                    'grades' => [],
                    'average' => 0.0,
                    'count' => 0
                ];
            }

            $grouped[$key]['grades'][] = [
                'studentId' => $grade->getStudent()->getId(),
                'fullName' => $grade->getStudent()->getUser() ? 
                    $grade->getStudent()->getUser()->getName() . ' ' . $grade->getStudent()->getUser()->getLastname() : 
                    'Étudiant inconnu',
                'grade' => $grade->getGrade()
            ];

            $grouped[$key]['count']++;
        }

        // Calculer les moyennes pour chaque contrôle
        foreach ($grouped as &$control) {
            $grades = array_map(fn($g) => [
                'grade' => $g['grade'],
                'divisor' => $control['divisor']
            ], $control['grades']);
            
            $control['average'] = $this->calculateAverage($grades);
        }

        // Trier par date de création décroissante
        uasort($grouped, fn($a, $b) => $b['createdAt'] <=> $a['createdAt']);

        return array_values($grouped);
    }

    /**
     * Récupère l'historique des saisies d'un professeur
     */
    public function getProfessorGradesHistory(Professor $professor, ?\DateTimeInterface $from = null, ?\DateTimeInterface $to = null): array
    {
        if (!$from || !$to) {
            $currentYear = date('Y');
            $from = new \DateTime("{$currentYear}-09-02");
            $to = new \DateTime("{$currentYear}-06-26");
        }

        $grades = $this->gradeRepository->findGradesByProfessorAndDateRange(
            $professor,
            $from,
            $to
        );

        $history = [];
        foreach ($grades as $grade) {
            $history[] = [
                'id' => $grade->getId(),
                'title' => $grade->getTitle(),
                'course' => $grade->getCourse() ? [
                    'id' => $grade->getCourse()->getId(),
                    'name' => $grade->getCourse()->getName()
                ] : null,
                'student' => $grade->getStudent() ? [
                    'id' => $grade->getStudent()->getId(),
                    'name' => $grade->getStudent()->getUser() ? 
                        $grade->getStudent()->getUser()->getName() . ' ' . $grade->getStudent()->getUser()->getLastname() : 
                        'Étudiant inconnu'
                ] : null,
                'grade' => $grade->getGrade(),
                'divisor' => $grade->getDividor(),
                'createdAt' => $grade->getCreatedAt()->format('c'),
                'semester' => $this->determineSemesterFromDate($grade->getCreatedAt())
            ];
        }

        // Trier par date de création décroissante
        usort($history, fn($a, $b) => $b['createdAt'] <=> $a['createdAt']);

        return $history;
    }
}
