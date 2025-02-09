<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class SemesterController extends AbstractController
{
    #[Route('/semester', name: 'app_semester')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/SemesterController.php',
        ]);
    }

    #[Route('/api/semesters', name: 'semester.getAll', methods:['GET'])]
    public function getAllSemesters(
        SemesterRepository $repository,
        SerializerInterface $serializer
        ): JsonResponse
    {
        $semester =  $repository->findAll();
        $jsonSemester = $serializer->serialize($semester, 'json',["groups" => "getAllSemesters"]);
        return new JsonResponse(    
            $jsonSemester,
            Response::HTTP_OK, 
            [], 
            true
        );
    }

    #[Route('/api/semester/{studentId}', name: 'semester.getOne', methods:['GET'])]
    public function getSemesterByStudentId(
        SemesterRepository $repository,
        SerializerInterface $serializer,
        int $studentId
        ): JsonResponse
    {
        $semester =  $repository->findBy(['student' => $studentId]);
        $jsonSemester = $serializer->serialize($semester, 'json',["groups" => "getAllSemesters"]);
        return new JsonResponse(    
            $jsonSemester,
            Response::HTTP_OK, 
            [], 
            true
        );
    }
    
}
