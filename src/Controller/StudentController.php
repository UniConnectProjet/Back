<?php

namespace App\Controller;

use App\Entity\Classe;
use App\Entity\Student;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\StudentRepository;
use App\Repository\ClasseRepository;
use App\Repository\UserRepository;
use App\Repository\SemesterRepository;
use App\Repository\AbsenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;

use Symfony\Component\HttpFoundation\Request;

class StudentController extends AbstractController
{
    private const ROUTE_FOR_A_STUDENT = '/api/students/{id}';
    private StudentRepository $repository;
    private SerializerInterface $serializer;

    public function __construct(StudentRepository $repository, SerializerInterface $serializer)
    {
        $this->repository = $repository;
        $this->serializer = $serializer;
    }
    #[Route('/student', name: 'app_student')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/StudentController.php',
        ]);
    }


    #[Route('/api/students', name: 'student.getAll', methods:['GET'])]
    public function getAllStudents(): JsonResponse
    {
        $student =  $this->repository->findAll();
        $jsonStudents = $this->serializer->serialize($student, 'json',["groups" => "getAllStudents"]);
        return new JsonResponse(    
            $jsonStudents,
            JsonResponse::HTTP_OK, 
            [], 
            true
        );
    }

    #[Route('/api/me/student', name: 'student.me', methods: ['GET'])]
    public function getMyStudent(StudentRepository $repo): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'Unauthorized'], 401);
        }

        $student = $repo->findOneBy(['user' => $user]);
        if (!$student) {
            return new JsonResponse(['message' => 'No student for this user'], 404);
        }

        // Réponse minimaliste → pas de groupes nécessaires, évite les cycles
        return new JsonResponse([
            'id'     => $student->getId(),
            'classe' => $student->getClasse() ? [
                'id'   => $student->getClasse()->getId(),
                'name' => $student->getClasse()->getName(),
            ] : null,
        ], 200);
    }

    #[Route(self::ROUTE_FOR_A_STUDENT, name: 'student.getOne', methods:['GET'])]
    public function getOneStudent(
        int $id
        ): JsonResponse
    {
        $student =  $this->repository->find($id);
        $jsonStudent = $this->serializer->serialize($student, 'json',["groups" => "getAllStudents"]);
        return new JsonResponse(    
            $jsonStudent,
            JsonResponse::HTTP_OK, 
            [], 
            true
        );
    }

    #[Route('/api/student/grades/{id}', name: 'student.getGrades', methods:['GET'])]
    #[Route('/api/students/{id}/grades', name: 'student.getGrades.alt', methods:['GET'])]
    public function getGradesByStudentId(
        int $id
        ): JsonResponse
    {
        $student =  $this->repository->find($id);
        $jsonStudent = $this->serializer->serialize($student, 'json',["groups" => "getStudentGrades"]);
        return new JsonResponse(    
            $jsonStudent,
            JsonResponse::HTTP_OK, 
            [], 
            true
        );
    }

    #[Route('/api/student/{id}/absences', name: 'student.getAbsences', methods:['GET'])]
    public function getAbsencesByStudentId(
        int $id
        ): JsonResponse
    {
        $student =  $this->repository->find($id);
        $jsonStudent = $this->serializer->serialize($student, 'json',["groups" => "getStudentAbsences"]);
        return new JsonResponse(    
            $jsonStudent,
            JsonResponse::HTTP_OK, 
            [], 
            true
        );
    }

    #[Route('/api/student/{id}/semesters/absences', name: 'student.getAbsencesBySemester', methods: ['GET'])]
    public function getAbsencesBySemestersForStudent(
        int $id
    ): JsonResponse {
        $student = $this->repository->find($id);
        if (!$student) {
            return new JsonResponse(['message' => 'Student not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $json = $this->serializer->serialize($student, 'json', [
            'groups' => ['getStudentAbsences'],
            \Symfony\Component\Serializer\Normalizer\DateTimeNormalizer::FORMAT_KEY => \DateTimeInterface::ATOM, // ISO 8601
        ]);
        
        return new JsonResponse($json, JsonResponse::HTTP_OK, [], true);
    }


   #[Route('/api/student', name: 'student.add', methods:['POST'])]
    public function addStudent(
        Request $request,
        EntityManagerInterface $em,
        ClasseRepository $classeRepository,
        UserRepository $userRepository,
        SemesterRepository $semesterRepository
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $student = new Student();

        // Classe
        $classe = $this->getClasseFromData($data, $classeRepository);
        if (!$classe) {
            return new JsonResponse(['error' => 'Classe not found or missing'], JsonResponse::HTTP_BAD_REQUEST);
        }
        $student->setClasse($classe);

        // User
        $user = $this->getUserFromData($data, $userRepository);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found or missing'], JsonResponse::HTTP_BAD_REQUEST);
        }
        $student->setUser($user);

        // Semesters
        $semesters = $this->getSemestersFromData($data, $semesterRepository);
        foreach ($semesters as $semester) {
            $student->addSemester($semester);
        }

        $em->persist($student);
        $em->flush();

        return new JsonResponse(
            ['message' => 'Student added successfully', 'id' => $student->getId()],
            JsonResponse::HTTP_CREATED
        );
    }

    #[Route(self::ROUTE_FOR_A_STUDENT, name: 'student.update', methods:['PUT'])]
    public function updateStudent(
        Request $request,
        EntityManagerInterface $em,
        ClasseRepository $classeRepository,
        UserRepository $userRepository,
        SemesterRepository $semesterRepository,
        int $id
    ): JsonResponse {
        $student = $this->repository->find($id);

        if (!$student) {
            return new JsonResponse(['error' => 'Student not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        // Classe
        $classe = $this->getClasseFromData($data, $classeRepository);
        if (isset($data['classe']['id']) && !$classe) {
            return new JsonResponse(['error' => 'Classe not found'], JsonResponse::HTTP_BAD_REQUEST);
        }
        if ($classe) {
            $student->setClasse($classe);
        }

        // User
        $user = $this->getUserFromData($data, $userRepository);
        if (isset($data['user']['id']) && !$user) {
            return new JsonResponse(['error' => 'User not found'], JsonResponse::HTTP_BAD_REQUEST);
        }
        if ($user) {
            $student->setUser($user);
        }

        // Semesters
        if (isset($data['semesters']) && is_array($data['semesters'])) {
            foreach ($student->getSemesters() as $semester) {
                $student->removeSemester($semester);
            }

            foreach ($this->getSemestersFromData($data, $semesterRepository) as $semester) {
                $student->addSemester($semester);
            }
        }

        $em->flush();

        return new JsonResponse(
            ['message' => 'Student updated successfully', 'id' => $student->getId()],
            JsonResponse::HTTP_OK
        );
    }

    #[Route(self::ROUTE_FOR_A_STUDENT, name: 'student.delete', methods:['DELETE'])]
    public function deleteStudent(
        EntityManagerInterface $em,
        int $id
        ): JsonResponse
    {
        $student = $this->repository->find($id);
        $em->remove($student);
        $em->flush();
        return new JsonResponse(
            'Student deleted successfully',
            JsonResponse::HTTP_OK,
            [],
            true
        );
    }

    private function getClasseFromData(array $data, ClasseRepository $classeRepository) : ?Classe
    {
        if (!isset($data['classe']['id'])) {
            return null;
        }

        return $classeRepository->find($data['classe']['id']);
    }

    private function getUserFromData(array $data, UserRepository $userRepository): ?User
    {
        if (!isset($data['user']['id'])) {
            return null;
        }

        return $userRepository->find($data['user']['id']);
    }

    private function getSemestersFromData(array $data, SemesterRepository $semesterRepository): array
    {
        $semesters = [];

        if (isset($data['semesters']) && is_array($data['semesters'])) {
            foreach ($data['semesters'] as $semesterData) {
                if (isset($semesterData['id'])) {
                    $semester = $semesterRepository->find($semesterData['id']);
                    if ($semester) {
                        $semesters[] = $semester;
                    }
                }
            }
        }

        return $semesters;
    }

}
