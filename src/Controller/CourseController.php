<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\CourseRepository;

class CourseController extends AbstractController
{
    #[Route('/course', name: 'app_course')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/CourseController.php',
        ]);
    }

    #[Route('/api/courses', name: 'course.getAll', methods:['GET'])]
    public function getAllCourses(
        CourseRepository $repository,
        SerializerInterface $serializer
        ): JsonResponse
    {
        $course =  $repository->findAll();
        $jsonCourse = $serializer->serialize($course, 'json',["groups" => "getAllCourses"]);
        return new JsonResponse(    
            $jsonCourse,
            Response::HTTP_OK, 
            [], 
            true
        );
    }
}
