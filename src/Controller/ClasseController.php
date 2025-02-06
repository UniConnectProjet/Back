<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class ClasseController extends AbstractController
{
    #[Route('/classe', name: 'app_classe')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/ClasseController.php',
        ]);
    }
    
    #[Route('/api/classes', name: 'classe.getAll', methods:['GET'])]
    public function getAllClasses(
        ClasseRepository $repository,
        SerializerInterface $serializer
        ): JsonResponse
    {
        $classe =  $repository->findAll();
        $jsonClasse = $serializer->serialize($course, 'json',["groups" => "getAllClasses"]);
        return new JsonResponse(    
            $jsonClasse,
            Response::HTTP_OK, 
            [], 
            true
        );
    }
}
