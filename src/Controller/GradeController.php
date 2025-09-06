<?php

namespace App\Controller;

use App\Entity\Student;
use App\Entity\Course;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\GradeRepository;
use App\Entity\Grade;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/api/grade')]
class GradeController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}
    #[Route('/grade', name: 'app_grade')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/GradeController.php',
        ]);
    }

    #[Route('/', name: 'grade.getAll', methods:['GET'])]
    public function getAllGrades(
        GradeRepository $repository,
        SerializerInterface $serializer
        ): JsonResponse
    {
        $grade =  $repository->findAll();
        $jsonGrades = $serializer->serialize($grade, 'json',["groups" => "getAllGrades"]);
        return new JsonResponse(    
            $jsonGrades,
            Response::HTTP_OK, 
            [], 
            true
        );
    }

    #[Route('/by-student/{studentId}', name: 'grade.getByStudent', methods:['GET'])]
    public function getGradesByStudentId(
        GradeRepository $repository,
        SerializerInterface $serializer,
        int $studentId
        ): JsonResponse
    {
        $grade =  $repository->findBy(['student' => $studentId]);
        $jsonGrade = $serializer->serialize($grade, 'json',["groups" => "getAllGrades"]);
        return new JsonResponse(    
            $jsonGrade,
            Response::HTTP_OK, 
            [], 
            true
        );
    }

    #[Route('/by-course/{courseId}', name: 'grade.getByCourse', methods:['GET'])]
    public function getGradesByCourseId(
        GradeRepository $repository,
        SerializerInterface $serializer,
        int $courseId
        ): JsonResponse
    {
        $grade =  $repository->findBy(['course' => $courseId]);
        $jsonGrade = $serializer->serialize($grade, 'json',["groups" => "getAllGrades"]);
        return new JsonResponse(    
            $jsonGrade,
            Response::HTTP_OK, 
            [], 
            true
        );
    }

    #[Route('/by-semester/{semesterId}', name: 'grade.getBySemester', methods:['GET'])]
    public function getGradesBySemesterId(
        GradeRepository $repository,
        SerializerInterface $serializer,
        int $semesterId
    ): JsonResponse {
        $grades = $repository->findBySemesterId($semesterId);

        if (!$grades) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        $json = $serializer->serialize($grades, 'json', ['groups' => 'getAllGrades']);
        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/student/{studentId}', name: 'grade.addForStudent', methods:['POST'])]
    public function addGradeForStudent(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        int $studentId
        ): JsonResponse
    {
        $data = $request->getContent();
        $grade = $serializer->deserialize($data, Grade::class, 'json');
        $student = $em->getRepository(Student::class)->find($studentId);
        
        if (!$student) {
            return new JsonResponse(['error' => 'Student not found'], Response::HTTP_NOT_FOUND);
        }
        $grade->setStudent($student);

        $em->persist($grade);
        $em->flush();
        return new JsonResponse(
            'Grade added',
            Response::HTTP_CREATED,
            [],
            true
        );
    }

    #[Route('/course/{courseId}', name: 'grade.addForCourse', methods:['POST'])]
    public function addGradeForCourse(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        int $courseId
        ): JsonResponse
    {
        $data = $request->getContent();
        $grade = $serializer->deserialize($data, Grade::class, 'json');
        $course = $em->getRepository(Course::class)->find($courseId);
        
        if (!$course) {
            return new JsonResponse(['error' => 'Course not found'], Response::HTTP_NOT_FOUND);
        }
        $grade->setCourse($course);

        $em->persist($grade);
        $em->flush();
        return new JsonResponse(
            'Grade added',
            Response::HTTP_CREATED,
            [],
            true
        );
    }

    #[Route('/{studentId}', name: 'grade.update', methods:['PUT'])]
    public function updateGrade(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        int $studentId
        ): JsonResponse
    {
        $data = $request->getContent();
        $grade = $serializer->deserialize($data, Grade::class, 'json');
        
        $student = $em->getRepository(Student::class)->find($studentId);
        if (!$student) {
            return new JsonResponse(['error' => 'Student not found'], Response::HTTP_NOT_FOUND);
        }
        $grade->setStudent($student);

        $em->persist($grade);
        $em->flush();
        return new JsonResponse(
            'Grade updated',
            Response::HTTP_OK,
            [],
            true
        );
    }

    #[Route('/{gradeId}', name: 'grade.delete', methods:['DELETE'])]
    public function deleteGrade(
        GradeRepository $repository,
        EntityManagerInterface $em,
        int $gradeId
        ): JsonResponse
    {
        $grade = $repository->find($gradeId);

        if (!$grade) {
            return new JsonResponse(['error' => 'Grade not found'], Response::HTTP_NOT_FOUND);
        }

        $em->remove($grade);
        $em->flush();
        return new JsonResponse(
            'Grade deleted',
            Response::HTTP_OK,
            [],
            true
        );
    }

    #[Route('/save', name: 'save_grades', methods: ['POST'])]
    public function saveGrades(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['classId']) || !isset($data['courseId']) || !isset($data['assignments']) || !isset($data['grades'])) {
            return $this->json(['error' => 'Données manquantes'], 400);
        }

        $classId = $data['classId'];
        $courseId = $data['courseId'];
        $assignments = $data['assignments'];
        $grades = $data['grades'];

        try {
            // Récupérer la classe et le cours
            $classe = $this->em->getRepository(\App\Entity\Classe::class)->find($classId);
            if (!$classe) {
                return $this->json(['error' => 'Classe non trouvée'], 404);
            }

            $course = $this->em->getRepository(\App\Entity\Course::class)->find($courseId);
            if (!$course) {
                return $this->json(['error' => 'Cours non trouvé'], 404);
            }

            $savedGrades = [];

            // Parcourir les notes de chaque étudiant
            foreach ($grades as $studentId => $studentGrades) {
                $student = $this->em->getRepository(\App\Entity\Student::class)->find($studentId);
                if (!$student) continue;

                // Parcourir les notes de chaque devoir
                foreach ($studentGrades as $assignmentId => $gradeData) {
                    if (empty($gradeData['score'])) continue; // Ignorer les notes vides

                    // Trouver le devoir correspondant
                    $assignment = array_filter($assignments, fn($a) => $a['id'] == $assignmentId);
                    if (empty($assignment)) continue;
                    $assignment = reset($assignment);

                    // Créer ou mettre à jour la note
                    $existingGrade = $this->em->getRepository(\App\Entity\Grade::class)
                        ->findOneBy([
                            'student' => $student,
                            'course' => $course,
                            'title' => $assignment['title']
                        ]);

                    if ($existingGrade) {
                        $existingGrade->setGrade($gradeData['score']);
                    } else {
                        $grade = new \App\Entity\Grade();
                        $grade->setStudent($student);
                        $grade->setCourse($course);
                        $grade->setTitle($assignment['title']);
                        $grade->setGrade($gradeData['score']);
                        $grade->setDividor($assignment['maxPoints']);
                        
                        $this->em->persist($grade);
                    }

                    $savedGrades[] = [
                        'studentId' => $studentId,
                        'assignmentId' => $assignmentId,
                        'score' => $gradeData['score'],
                        'comment' => $gradeData['comment'] ?? ''
                    ];
                }
            }

            $this->em->flush();

            return $this->json([
                'success' => true,
                'message' => 'Notes enregistrées avec succès',
                'savedGrades' => $savedGrades
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors de l\'enregistrement: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/student/{studentId}', name: 'get_student_grades', methods: ['GET'])]
    public function getStudentGrades(int $studentId): JsonResponse
    {
        $student = $this->em->getRepository(\App\Entity\Student::class)->find($studentId);
        if (!$student) {
            return $this->json(['error' => 'Étudiant non trouvé'], 404);
        }

        $grades = $this->em->getRepository(\App\Entity\Grade::class)
            ->createQueryBuilder('g')
            ->select('g.title, g.grade as score, g.dividor as outOf, c.name as courseName')
            ->leftJoin('g.course', 'c')
            ->where('g.student = :student')
            ->setParameter('student', $student)
            ->orderBy('g.id', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->json([
            'student' => [
                'id' => $student->getId(),
                'name' => $student->getUser()?->getName(),
                'lastname' => $student->getUser()?->getLastname(),
                'email' => $student->getUser()?->getEmail()
            ],
            'grades' => $grades
        ]);
    }

}
