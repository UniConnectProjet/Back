<?php

namespace App\Controller;

use App\Entity\Category;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

final class CategoryController extends AbstractController
{
    #[Route('/category', name: 'app_category')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/CategoryController.php',
        ]);
    }

    #[Route('/api/categories/{id}/courses', name: 'category.courses', methods: ['GET'])]
    public function getCoursesByCategory(
        Category $category,
        SerializerInterface $serializer
    ): JsonResponse {
        $courses = [];
        foreach ($category->getCourseUnits() as $courseUnit) {
            foreach ($courseUnit->getCourses() as $course) {
                $courses[] = $course;
            }
        }
        $json = $serializer->serialize($courses, 'json', ['groups' => ['getAllCourses']]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/api/categories/{id}/levels', name: 'category.levels', methods: ['GET'])]
    public function getLevelsByCategory(
        Category $category,
        SerializerInterface $serializer
    ): JsonResponse {
        $levels = $category->getLevelId();

        $json = $serializer->serialize($levels, 'json', ['groups' => 'getLevelsByCategory']);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

}
