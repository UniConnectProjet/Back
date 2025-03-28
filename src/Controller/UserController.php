<?php

namespace App\Controller;

use App\Repository\UserRepository;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class UserController extends AbstractController
{
    #[Route('/user', name: 'app_user')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/UserController.php',
        ]);
    }

    #[Route('/api/users', name: 'users.getAll', methods:['GET'])]
    public function getAllUsers(
        UserRepository $repository,
        SerializerInterface $serializer
        ): JsonResponse
    {
        $user =  $repository->findAll();
        $jsonUsers = $serializer->serialize($user, 'json',["groups" => "getAllUsers"]);
        return new JsonResponse(    
            $jsonUsers,
            Response::HTTP_OK, 
            [], 
            true
        );
    }

    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login()
    {
        $user = $this->getUser();
        return new JsonResponse([
            'username' => $user->getUserIdentifier(),
            'roles' => $user->getRoles()
        ]);
    }
}
