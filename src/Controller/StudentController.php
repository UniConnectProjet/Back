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
}
