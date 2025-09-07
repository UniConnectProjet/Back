<?php

namespace App\Controller;

use App\Entity\Professor;
use App\Entity\Course;
use App\Entity\Classe;
use App\Entity\Grade;
use App\Service\ProfessorGradeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/api/prof')]
#[IsGranted('ROLE_PROFESSOR')]
class ProfessorGradeController extends AbstractController
{
    public function __construct(
        private ProfessorGradeService $professorGradeService,
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer
    ) {}

    /**
     * Vue synthèse du professeur - toutes les notes par cours et classe
     */
    #[Route('/grades/overview', name: 'prof_grades_overview', methods: ['GET'])]
    public function getGradesOverview(Request $request): JsonResponse
    {
        $professor = $this->getUser()->getProfessor();
        
        if (!$professor) {
            return new JsonResponse(['error' => 'Professeur non trouvé'], 404);
        }

        $from = $request->query->get('from') ? new \DateTime($request->query->get('from')) : null;
        $to = $request->query->get('to') ? new \DateTime($request->query->get('to')) : null;

        try {
            $overview = $this->professorGradeService->getProfessorGradesOverview($professor, $from, $to);
            return new JsonResponse($overview);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur lors de la récupération des notes: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Notes détaillées pour un cours et une classe spécifiques
     */
    #[Route('/courses/{courseId}/classes/{classId}/grades', name: 'prof_course_class_grades', methods: ['GET'])]
    public function getCourseClassGrades(int $courseId, int $classId, Request $request): JsonResponse
    {
        $professor = $this->getUser()->getProfessor();
        
        if (!$professor) {
            return new JsonResponse(['error' => 'Professeur non trouvé'], 404);
        }

        // Vérifier que le cours appartient au professeur
        $course = $this->entityManager->getRepository(Course::class)->find($courseId);
        if (!$course || !$professor->getCourses()->contains($course)) {
            return new JsonResponse(['error' => 'Cours non trouvé ou non autorisé'], 404);
        }

        // Vérifier que la classe est associée au cours
        $class = $this->entityManager->getRepository(Classe::class)->find($classId);
        if (!$class || !$course->getClasses()->contains($class)) {
            return new JsonResponse(['error' => 'Classe non trouvée ou non associée au cours'], 404);
        }

        $from = $request->query->get('from') ? new \DateTime($request->query->get('from')) : null;
        $to = $request->query->get('to') ? new \DateTime($request->query->get('to')) : null;

        try {
            // Récupérer les notes pour ce cours et cette classe
            $qb = $this->entityManager->createQueryBuilder();
            $qb->select('g')
               ->from(Grade::class, 'g')
               ->join('g.student', 's')
               ->where('g.course = :course')
               ->andWhere('s.classe = :classe')
               ->setParameter('course', $course)
               ->setParameter('classe', $class);
            
            $grades = $qb->getQuery()->getResult();
            
            // Log pour déboguer
            error_log('Grades trouvées pour cours ' . $courseId . ' et classe ' . $classId . ': ' . count($grades));
            
            // Grouper les notes par titre de contrôle
            $groupedGrades = [];
            foreach ($grades as $grade) {
                $title = $grade->getTitle();
                if (!isset($groupedGrades[$title])) {
                    $groupedGrades[$title] = [
                        'title' => $title,
                        'createdAt' => $grade->getCreatedAt(),
                        'divisor' => $grade->getDividor(),
                        'grades' => []
                    ];
                }
                
                $groupedGrades[$title]['grades'][] = [
                    'id' => $grade->getId(),
                    'studentId' => $grade->getStudent() ? $grade->getStudent()->getId() : null,
                    'grade' => $grade->getGrade(),
                    'comment' => ''
                ];
            }
            
            // Transformer en format API
            $controls = [];
            foreach ($groupedGrades as $group) {
                $total = 0;
                $count = count($group['grades']);
                foreach ($group['grades'] as $grade) {
                    $total += $grade['grade'];
                }
                $average = $count > 0 ? round($total / $count, 2) : 0;
                
                $controls[] = [
                    'title' => $group['title'],
                    'createdAt' => $group['createdAt']->format('c'),
                    'semester' => 'S1', // Par défaut
                    'divisor' => $group['divisor'],
                    'average' => $average,
                    'count' => $count,
                    'grades' => $group['grades']
                ];
            }
            
            return new JsonResponse([
                'course' => [
                    'id' => $courseId,
                    'title' => $course->getName()
                ],
                'class' => [
                    'id' => $classId,
                    'label' => $class->getName()
                ],
                'controls' => $controls
            ]);
        } catch (\Exception $e) {
            error_log('Erreur dans getCourseClassGrades: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            return new JsonResponse(['error' => 'Erreur lors de la récupération des notes: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Test simple pour récupérer les notes
     */
    #[Route('/test-grades/{courseId}/{classId}', name: 'prof_test_grades', methods: ['GET'])]
    public function testGrades(int $courseId, int $classId): JsonResponse
    {
        try {
            // Récupérer les notes directement
            $grades = $this->entityManager->getRepository(Grade::class)->findBy([
                'course' => $courseId
            ]);
            
            $result = [];
            foreach ($grades as $grade) {
                $student = $grade->getStudent();
                $studentClassId = $student && $student->getClasse() ? $student->getClasse()->getId() : null;
                
                $result[] = [
                    'id' => $grade->getId(),
                    'title' => $grade->getTitle(),
                    'grade' => $grade->getGrade(),
                    'divisor' => $grade->getDividor(),
                    'studentId' => $student ? $student->getId() : null,
                    'studentClassId' => $studentClassId,
                    'courseId' => $grade->getCourse() ? $grade->getCourse()->getId() : null,
                    'createdAt' => $grade->getCreatedAt() ? $grade->getCreatedAt()->format('c') : null
                ];
            }
            
            return new JsonResponse([
                'courseId' => $courseId,
                'classId' => $classId,
                'grades' => $result,
                'count' => count($result),
                'filteredCount' => count(array_filter($result, function($g) use ($classId) {
                    return $g['studentClassId'] == $classId;
                }))
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Historique des saisies du professeur
     */
    #[Route('/grades/history', name: 'prof_grades_history', methods: ['GET'])]
    public function getGradesHistory(Request $request): JsonResponse
    {
        $professor = $this->getUser()->getProfessor();
        
        if (!$professor) {
            return new JsonResponse(['error' => 'Professeur non trouvé'], 404);
        }

        $from = $request->query->get('from') ? new \DateTime($request->query->get('from')) : null;
        $to = $request->query->get('to') ? new \DateTime($request->query->get('to')) : null;

        try {
            $history = $this->professorGradeService->getProfessorGradesHistory($professor, $from, $to);
            return new JsonResponse($history);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur lors de la récupération de l\'historique: ' . $e->getMessage()], 500);
        }
    }
}
