<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\StudentRepository;

class StudentController extends AbstractController
{
    #[Route('/student', name: 'app_student')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/StudentController.php',
        ]);
    }

    #[Route('/api/students', name: 'student.getAll', methods:['GET'])]
    public function getAllStudents(
        StudentRepository $repository,
        SerializerInterface $serializer
        ): JsonResponse
    {
        $student =  $repository->findAll();
        $jsonStudents = $serializer->serialize($student, 'json',["groups" => "getAllStudents"]);
        return new JsonResponse(    
            $jsonStudents,
            Response::HTTP_OK, 
            [], 
            true
        );
    }

    #[Route('/api/students/{id}', name: 'student.getOne', methods:['GET'])]
    public function getOneStudent(
        StudentRepository $repository,
        SerializerInterface $serializer,
        int $id
        ): JsonResponse
    {
        $student =  $repository->find($id);
        $jsonStudent = $serializer->serialize($student, 'json',["groups" => "getAllStudents"]);
        return new JsonResponse(    
            $jsonStudent,
            Response::HTTP_OK, 
            [], 
            true
        );
    }

    #[Route('/api/students/{id}', name: 'student.getGrades', methods:['GET'])]
    public function getGradesByStudentId(
        StudentRepository $repository,
        SerializerInterface $serializer,
        int $id
        ): JsonResponse
    {
        $student =  $repository->find($id);
        $jsonStudent = $serializer->serialize($student, 'json',["groups" => "getAllStudents"]);
        return new JsonResponse(    
            $jsonStudent,
            Response::HTTP_OK, 
            [], 
            true
        );
    }

    #[Route('/api/students/{id}', name: 'student.getAbsences', methods:['GET'])]
    public function getAbsencesByStudentId(
        StudentRepository $repository,
        SerializerInterface $serializer,
        int $id
        ): JsonResponse
    {
        $student =  $repository->find($id);
        $jsonStudent = $serializer->serialize($student, 'json',["groups" => "getAllStudents"]);
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
        SerializerInterface $serializer,
        EntityManagerInterface $em
        ): JsonResponse
    {
        $data = $request->getContent();
        $student = $serializer->deserialize($data, Student::class, 'json');
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
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        int $id
        ): JsonResponse
    {
        $student = $repository->find($id);
        $data = $request->getContent();
        $student = $serializer->deserialize($data, Student::class, 'json');
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
        StudentRepository $repository,
        EntityManagerInterface $em,
        int $id
        ): JsonResponse
    {
        $student = $repository->find($id);
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
