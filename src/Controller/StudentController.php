<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\StudentRepository;
use Symfony\Component\Serializer\SerializerInterface;

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

    #[Route('/api/students/{id}', name: 'student.getGrades', methods:['GET'])]
    public function getGradesByStudentId(
        int $id
        ): JsonResponse
    {
        $student =  $this->repository->find($id);
        $jsonStudent = $this->serializer->serialize($student, 'json',["groups" => "getAllStudents"]);
        return new JsonResponse(    
            $jsonStudent,
            Response::HTTP_OK, 
            [], 
            true
        );
    }

    #[Route('/api/students/{id}', name: 'student.getAbsences', methods:['GET'])]
    public function getAbsencesByStudentId(
        int $id
        ): JsonResponse
    {
        $student =  $this->repository->find($id);
        $jsonStudent = $this->serializer->serialize($student, 'json',["groups" => "getAllStudents"]);
        return new JsonResponse(    
            $jsonStudent,
            Response::HTTP_OK, 
            [], 
            true
        );
    }

    #[Route('/api/students', name: 'student.add', methods:['POST'])]
    public function addStudent(
        Request $request,
        EntityManagerInterface $em
        ): JsonResponse
    {
        $data = $request->getContent();
        $student = $this->serializer->deserialize($data, Student::class, 'json');
        $em->persist($student);
        $em->flush();
        return new JsonResponse(
            'Student added successfully',
            Response::HTTP_CREATED,
            [],
            true
        );
    }

    #[Route('/api/students/{id}', name: 'student.update', methods:['PUT'])]
    public function updateStudent(
        Request $request,
        EntityManagerInterface $em,
        int $id
        ): JsonResponse
    {
        $student = $this->repository->find($id);
        $data = $request->getContent();
        $student = $this->serializer->deserialize($data, Student::class, 'json');
        $em->persist($student);
        $em->flush();
        return new JsonResponse(
            'Student updated successfully',
            Response::HTTP_OK,
            [],
            true
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
            Response::HTTP_OK,
            [],
            true
        );
    }

}
