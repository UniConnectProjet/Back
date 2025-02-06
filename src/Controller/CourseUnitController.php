<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\CourseUnitRepository;

class CourseUnitController extends AbstractController
{
    #[Route('/course/unit', name: 'app_course_unit')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/CourseUnitController.php',
        ]);
    }

    #[Route('/api/courses/unit', name: 'courseUnit.getAll', methods:['GET'])]
    public function getAllCoursesUnit(
        CourseUnitRepository $repository,
        SerializerInterface $serializer
        ): JsonResponse
    {
        $courseUnit =  $repository->findAll();
        $jsonCourseUnit = $serializer->serialize($courseUnit, 'json',["groups" => "getAllCoursesUnit"]);
        return new JsonResponse(    
            $jsonCourseUnit,
            Response::HTTP_OK, 
            [], 
            true
        );
    }
}
