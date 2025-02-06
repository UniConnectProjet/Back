<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\GradeRepository;

class GradeController extends AbstractController
{
    #[Route('/grade', name: 'app_grade')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/GradeController.php',
        ]);
    }

    #[Route('/api/grades', name: 'grade.getAll', methods:['GET'])]
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
}
