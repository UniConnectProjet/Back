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

    #[Route('/api/grades/{studentId}', name: 'grade.getOne', methods:['GET'])]
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

    #[Route('/api/grades/{courseId}', name: 'grade.getOne', methods:['GET'])]
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

    #[Route('/api/grades/{semesterId}', name: 'grade.getOne', methods:['GET'])]
    public function getGradesBySemesterId(
        GradeRepository $repository,
        SerializerInterface $serializer,
        int $semesterId
        ): JsonResponse
    {
        $grade =  $repository->findBy(['semester' => $semesterId]);
        $jsonGrade = $serializer->serialize($grade, 'json',["groups" => "getAllGrades"]);
        return new JsonResponse(    
            $jsonGrade,
            Response::HTTP_OK, 
            [], 
            true
        );
    }

    #[Route('/api/grades', name: 'grade.add', methods:['POST'])]
    public function addGrade(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em
        ): JsonResponse
    {
        $data = $request->getContent();
        $grade = $serializer->deserialize($data, Grade::class, 'json');
        $em->persist($grade);
        $em->flush();
        return new JsonResponse(
            'Grade added',
            Response::HTTP_CREATED,
            [],
            true
        );
    }

    #[Route('/api/grades/{studentId}', name: 'grade.update', methods:['PUT'])]
    public function updateGrade(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        int $studentId
        ): JsonResponse
    {
        $data = $request->getContent();
        $grade = $serializer->deserialize($data, Grade::class, 'json');
        $grade->setStudent($studentId);
        $em->persist($grade);
        $em->flush();
        return new JsonResponse(
            'Grade updated',
            Response::HTTP_OK,
            [],
            true
        );
    }

    #[Route('/api/grades/{studentId}', name: 'grade.delete', methods:['DELETE'])]
    public function deleteGrade(
        GradeRepository $repository,
        EntityManagerInterface $em,
        int $studentId
        ): JsonResponse
    {
        $grade = $repository->find($studentId);
        $em->remove($grade);
        $em->flush();
        return new JsonResponse(
            'Grade deleted',
            Response::HTTP_OK,
            [],
            true
        );
    }

}
