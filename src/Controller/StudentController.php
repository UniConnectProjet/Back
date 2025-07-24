<?php

namespace App\Controller;

use App\Entity\Student;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\StudentRepository;
use App\Repository\ClasseRepository;
use App\Repository\UserRepository;
use App\Repository\SemesterRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;

use Symfony\Component\HttpFoundation\Request;

class StudentController extends AbstractController
{

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

    #[Route('/api/students/{id}', name: 'student.getOne', methods:['GET'])]
    public function getOneStudent(int $id): JsonResponse
    {
        $student =  $this->repository->find($id);

        if (!$student) {
            return new JsonResponse(['error' => 'Student not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $jsonStudent = $this->serializer->serialize($student, 'json', ["groups" => "getAllStudents"]);
        return new JsonResponse($jsonStudent, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/api/student/grades/{id}', name: 'student.getGrades', methods:['GET'])]
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

    #[Route('/api/student/absences/{id}', name: 'student.getAbsences', methods:['GET'])]
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

    #[Route('/api/student', name: 'student.add', methods:['POST'])]
    public function addStudent(
        Request $request,
        EntityManagerInterface $em,
        ClasseRepository $classeRepository,
        UserRepository $userRepository,
        SemesterRepository $semesterRepository
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        // Créer l'étudiant de base
        $student = new Student();
        
        // Gérer la relation avec la classe
        if (isset($data['classe']['id'])) {
            $classe = $classeRepository->find($data['classe']['id']);
            if (!$classe) {
                return new JsonResponse(['error' => 'Classe not found'], JsonResponse::HTTP_BAD_REQUEST);
            }
            $student->setClasse($classe);
        } else {
            return new JsonResponse(['error' => 'Classe is required'], JsonResponse::HTTP_BAD_REQUEST);
        }
        
        // Gérer la relation avec l'utilisateur
        if (isset($data['user']['id'])) {
            $user = $userRepository->find($data['user']['id']);
            if (!$user) {
                return new JsonResponse(['error' => 'User not found'], JsonResponse::HTTP_BAD_REQUEST);
            }
            $student->setUser($user);
        } else {
            return new JsonResponse(['error' => 'User is required'], JsonResponse::HTTP_BAD_REQUEST);
        }
        
        // Gérer la relation avec les semestres
        if (isset($data['semesters']) && is_array($data['semesters'])) {
            foreach ($data['semesters'] as $semesterData) {
                if (isset($semesterData['id'])) {
                    $semester = $semesterRepository->find($semesterData['id']);
                    if ($semester) {
                        $student->addSemester($semester);
                    }
                }
            }
        }
        
        $em->persist($student);
        $em->flush();
        return new JsonResponse(
            ['message' => 'Student added successfully', 'id' => $student->getId()],
            JsonResponse::HTTP_CREATED
        );
    }

    #[Route('/api/students/{id}', name: 'student.update', methods:['PUT'])]
    public function updateStudent(
        Request $request,
        EntityManagerInterface $em,
        ClasseRepository $classeRepository,
        UserRepository $userRepository,
        SemesterRepository $semesterRepository,
        int $id
    ): JsonResponse
    {
        $student = $this->repository->find($id);
        
        if (!$student) {
            return new JsonResponse(['error' => 'Student not found'], JsonResponse::HTTP_NOT_FOUND);
        }
        
        $data = json_decode($request->getContent(), true);
        
        // Mettre à jour la relation avec la classe
        if (isset($data['classe']['id'])) {
            $classe = $classeRepository->find($data['classe']['id']);
            if (!$classe) {
                return new JsonResponse(['error' => 'Classe not found'], JsonResponse::HTTP_BAD_REQUEST);
            }
            $student->setClasse($classe);
        }
        
        // Mettre à jour la relation avec l'utilisateur
        if (isset($data['user']['id'])) {
            $user = $userRepository->find($data['user']['id']);
            if (!$user) {
                return new JsonResponse(['error' => 'User not found'], JsonResponse::HTTP_BAD_REQUEST);
            }
            $student->setUser($user);
        }
        
        // Mettre à jour la relation avec les semestres
        if (isset($data['semesters']) && is_array($data['semesters'])) {
            // Supprimer tous les semestres actuels
            foreach ($student->getSemesters() as $semester) {
                $student->removeSemester($semester);
            }
            
            // Ajouter les nouveaux semestres
            foreach ($data['semesters'] as $semesterData) {
                if (isset($semesterData['id'])) {
                    $semester = $semesterRepository->find($semesterData['id']);
                    if ($semester) {
                        $student->addSemester($semester);
                    }
                }
            }
        }
        
        $em->flush();
        
        return new JsonResponse(
            ['message' => 'Student updated successfully', 'id' => $student->getId()],
            JsonResponse::HTTP_OK
        );
    }

    #[Route('/api/students/{id}', name: 'student.delete', methods:['DELETE'])]
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

}
