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

    #[Route('/api/semester/{courseUnitId}', name: 'semester.getOne', methods:['GET'])]
    public function getSemesterByCourseUnitId(
        SemesterRepository $repository,
        SerializerInterface $serializer,
        int $courseUnitId
        ): JsonResponse
    {
        $semester =  $repository->findBy(['courseUnit' => $courseUnitId]);
        $jsonSemester = $serializer->serialize($semester, 'json',["groups" => "getAllSemesters"]);
        return new JsonResponse(    
            $jsonSemester,
            Response::HTTP_OK, 
            [], 
            true
        );
    }

    #[Route('/api/semester/{classeId}', name: 'semester.getOne', methods:['GET'])]
    public function getSemesterByClasseId(
        SemesterRepository $repository,
        SerializerInterface $serializer,
        int $classeId
        ): JsonResponse
    {
        $semester =  $repository->findBy(['classe' => $classeId]);
        $jsonSemester = $serializer->serialize($semester, 'json',["groups" => "getAllSemesters"]);
        return new JsonResponse(    
            $jsonSemester,
            Response::HTTP_OK, 
            [], 
            true
        );
    }

    #[Route('/api/semester/{absenceId}', name: 'semester.getOne', methods:['GET'])]
    public function getSemesterByAbsenceId(
        SemesterRepository $repository,
        SerializerInterface $serializer,
        int $absenceId
        ): JsonResponse
    {
        $semester =  $repository->findBy(['absence' => $absenceId]);
        $jsonSemester = $serializer->serialize($semester, 'json',["groups" => "getAllSemesters"]);
        return new JsonResponse(    
            $jsonSemester,
            Response::HTTP_OK, 
            [], 
            true
        );
    }

    #[Route('/api/semester/{studentId}/{courseUnitId}', name: 'semester.getOne', methods:['GET'])]
    public function getSemesterByStudentIdAndCourseUnitId(
        SemesterRepository $repository,
        SerializerInterface $serializer,
        int $studentId,
        int $courseUnitId
        ): JsonResponse
    {
        $semester =  $repository->findBy(['student' => $studentId, 'courseUnit' => $courseUnitId]);
        $jsonSemester = $serializer->serialize($semester, 'json',["groups" => "getAllSemesters"]);
        return new JsonResponse(    
            $jsonSemester,
            Response::HTTP_OK, 
            [], 
            true
        );
    }

    #[Route('/api/semester/{studentId}/{classeId}', name: 'semester.getOne', methods:['GET'])]
    public function getSemesterByStudentIdAndClasseId(
        SemesterRepository $repository,
        SerializerInterface $serializer,
        int $studentId,
        int $classeId
        ): JsonResponse
    {
        $semester =  $repository->findBy(['student' => $studentId, 'classe' => $classeId]);
        $jsonSemester = $serializer->serialize($semester, 'json',["groups" => "getAllSemesters"]);
        return new JsonResponse(    
            $jsonSemester,
            Response::HTTP_OK, 
            [], 
            true
        );
    }

    #[Route('/api/semester/{studentId}/{absenceId}', name: 'semester.getOne', methods:['GET'])]
    public function getSemesterByStudentIdAndAbsenceId(
        SemesterRepository $repository,
        SerializerInterface $serializer,
        int $studentId,
        int $absenceId
        ): JsonResponse
    {
        $semester =  $repository->findBy(['student' => $studentId, 'absence' => $absenceId]);
        $jsonSemester = $serializer->serialize($semester, 'json',["groups" => "getAllSemesters"]);
        return new JsonResponse(    
            $jsonSemester,
            Response::HTTP_OK, 
            [], 
            true
        );
    }

    #[Route('/api/semester/{courseUnitId}/{classeId}', name: 'semester.getOne', methods:['GET'])]
    public function getSemesterByCourseUnitIdAndClasseId(
        SemesterRepository $repository,
        SerializerInterface $serializer,
        int $courseUnitId,
        int $classeId
        ): JsonResponse
    {
        $semester =  $repository->findBy(['courseUnit' => $courseUnitId, 'classe' => $classeId]);
        $jsonSemester = $serializer->serialize($semester, 'json',["groups" => "getAllSemesters"]);
        return new JsonResponse(    
            $jsonSemester,
            Response::HTTP_OK, 
            [], 
            true
        );
    }

    #[Route('/api/semester/{courseUnitId}/{absenceId}', name: 'semester.getOne', methods:['GET'])]
    public function getSemesterByCourseUnitIdAndAbsenceId(
        SemesterRepository $repository,
        SerializerInterface $serializer,
        int $courseUnitId,
        int $absenceId
        ): JsonResponse
    {
        $semester =  $repository->findBy(['courseUnit' => $courseUnitId, 'absence' => $absenceId]);
        $jsonSemester = $serializer->serialize($semester, 'json',["groups" => "getAllSemesters"]);
        return new JsonResponse(    
            $jsonSemester,
            Response::HTTP_OK, 
            [], 
            true
        );
    }

    #[Route('/api/semester/{classeId}/{absenceId}', name: 'semester.getOne', methods:['GET'])]
    public function getSemesterByClasseIdAndAbsenceId(
        SemesterRepository $repository,
        SerializerInterface $serializer,
        int $classeId,
        int $absenceId
        ): JsonResponse
    {
        $semester =  $repository->findBy(['classe' => $classeId, 'absence' => $absenceId]);
        $jsonSemester = $serializer->serialize($semester, 'json',["groups" => "getAllSemesters"]);
        return new JsonResponse(    
            $jsonSemester,
            Response::HTTP_OK, 
            [], 
            true
        );
    }

    #[Route('/api/semester/{studentId}/{courseUnitId}/{classeId}', name: 'semester.getOne', methods:['GET'])]
    public function getSemesterByStudentIdAndCourseUnitIdAndClasseId(
        SemesterRepository $repository,
        SerializerInterface $serializer,
        int $studentId,
        int $courseUnitId,
        int $classeId
        ): JsonResponse
    {
        $semester =  $repository->findBy(['student' => $studentId, 'courseUnit' => $courseUnitId, 'classe' => $classeId]);
        $jsonSemester = $serializer->serialize($semester, 'json',["groups" => "getAllSemesters"]);
        return new JsonResponse(    
            $jsonSemester,
            Response::HTTP_OK, 
            [], 
            true
        );
    }

    #[Route('/api/semester/{studentId}/{courseUnitId}/{absenceId}', name: 'semester.getOne', methods:['GET'])]
    public function getSemesterByStudentIdAndCourseUnitIdAndAbsenceId(
        SemesterRepository $repository,
        SerializerInterface $serializer,
        int $studentId,
        int $courseUnitId,
        int $absenceId
        ): JsonResponse
    {
        $semester =  $repository->findBy(['student' => $studentId, 'courseUnit' => $courseUnitId, 'absence' => $absenceId]);
        $jsonSemester = $serializer->serialize($semester, 'json',["groups" => "getAllSemesters"]);
        return new JsonResponse(    
            $jsonSemester,
            Response::HTTP_OK, 
            [], 
            true
        );
    }
    
    #[Route('/api/semester/{studentId}/{classeId}/{absenceId}', name: 'semester.getOne', methods:['GET'])]
    public function getSemesterByStudentIdAndClasseIdAndAbsenceId(
        SemesterRepository $repository,
        SerializerInterface $serializer,
        int $studentId,
        int $classeId,
        int $absenceId
        ): JsonResponse
    {
        $semester =  $repository->findBy(['student' => $studentId, 'classe' => $classeId, 'absence' => $absenceId]);
        $jsonSemester = $serializer->serialize($semester, 'json',["groups" => "getAllSemesters"]);
        return new JsonResponse(    
            $jsonSemester,
            Response::HTTP_OK, 
            [], 
            true
        );
    }
    
}
