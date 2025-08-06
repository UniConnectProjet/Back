<?php

namespace App\Controller;

use App\Repository\LevelRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/levels')]
final class LevelController extends AbstractController
{
    private LevelRepository $repository;
    private SerializerInterface $serializer;

    public function __construct(LevelRepository $repository, SerializerInterface $serializer)
    {
        $this->repository = $repository;
        $this->serializer = $serializer;
    }

    #[Route('/level', name: 'app_level')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/LevelController.php',
        ]);
    }

    #[Route('/', name: 'level.getAll', methods: ['GET'])]
    public function getAllLevels(): JsonResponse
    {
        $levels = $this->repository->findAll();
        $jsonLevel = $this->serializer->serialize($levels, 'json', ['groups' => 'getAllLevels']);

        return new JsonResponse($jsonLevel, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'level.getOne', methods: ['GET'])]
    public function getLevel(int $id): JsonResponse
    {
        $level = $this->repository->find($id);
        if (!$level) {
            return new JsonResponse(['error' => 'Level not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $jsonLevel = $this->serializer->serialize($level, 'json', ['groups' => 'getAllLevels']);
        return new JsonResponse($jsonLevel, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/{id}/classes', name: 'level.getClasses', methods: ['GET'])]
    public function getClassesFromLevel(int $id): JsonResponse
    {
        $level = $this->repository->find($id);
        if (!$level) {
            return new JsonResponse(['error' => 'Level not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $jsonLevel = $this->serializer->serialize($level, 'json', ['groups' => 'getLevelClasses']);
        return new JsonResponse($jsonLevel, JsonResponse::HTTP_OK, [], true);
    }
}
