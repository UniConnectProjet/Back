<?php

namespace App\Controller;

use App\Repository\SemesterRepository;
use App\Repository\StudentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/semesters')]
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

    #[Route('/', name: 'semester.getAll', methods:['GET'])]
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
}
