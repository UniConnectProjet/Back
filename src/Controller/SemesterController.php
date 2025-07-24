<?php

namespace App\Controller;

use App\Repository\SemesterRepository;
use App\Repository\StudentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

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
        StudentRepository $studentRepo,
        SerializerInterface $serializer,
        int $studentId
    ): JsonResponse
    {
        $student = $studentRepo->find($studentId);

        if (!$student) {
            return new JsonResponse(['error' => 'Student not found'], Response::HTTP_NOT_FOUND);
        }

        $semesters = $student->getSemesters(); // ici on accède à la relation

        $jsonSemester = $serializer->serialize($semesters, 'json', ["groups" => "getAllSemesters"]);

        return new JsonResponse(
            $jsonSemester,
            Response::HTTP_OK,
            [],
            true
        );
    }
    
}
