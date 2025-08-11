<?php

namespace App\Controller;

use App\Repository\UserRepository;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\User;
use Symfony\Component\Serializer\SerializerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/api/users')]
class UserController extends AbstractController
{
    private UserRepository $repository;
    private SerializerInterface $serializer;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(
        UserRepository $repository,
        SerializerInterface $serializer,
        UserPasswordHasherInterface $passwordHasher,
    ) {
        $this->repository = $repository;
        $this->serializer = $serializer;
        $this->passwordHasher = $passwordHasher;
    }

    #[Route('/user', name: 'app_user')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/UserController.php',
        ]);
    }

    #[Route('/', name: 'users.getAll', methods:['GET'])]
    public function getAllUsers(): JsonResponse
    {
        $user =  $this->repository->findAll();
        $jsonUsers = $this->serializer->serialize($user, 'json',["groups" => "getAllUsers"]);
        return new JsonResponse(    
            $jsonUsers,
            JsonResponse::HTTP_OK, 
            [], 
            true
        );
    }

    #[Route('/{email}', name: 'user.get', methods: ['GET'])]
    public function getUserId(string $email): JsonResponse
    {
        $currentUser = $this->getUser();

        if (!$currentUser || $currentUser->getUserIdentifier() !== $email) {
            return new JsonResponse(['error' => 'Unauthorized'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $user = $this->repository->findOneBy(['email' => $email]);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        return $this->json(['id' => $user->getId()]);
    }

    #[Route('/', name: 'user.create', methods:['POST'])]
    public function createUser(
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return new JsonResponse(
                ['message' => 'Invalid data'],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }
        $user = new User();
        $user->setName($data['name']);
        $user->setLastname($data['lastname']);
        $user->setEmail($data['email']);
        
        // Hashage de mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        $user->setBirthday(new \DateTime($data['birthday']));
        
        $user->setRoles(['ROLE_USER']);
        
        $entityManager->persist($user);
        $entityManager->flush();
        
        return new JsonResponse(
            ['message' => 'User created'],
            JsonResponse::HTTP_CREATED
        );
    }

    #[Route('/{id}', name: 'user.update', methods:['PUT'])]
    public function updateUser(
        int $id,
        Request $request
        ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return new JsonResponse(
                ['message' => 'Invalid data'],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }
        $user =  $this->repository->find($id);
        if (!$user) {
            return new JsonResponse(
                ['message' => 'User not found'],
                JsonResponse::HTTP_NOT_FOUND
            );
        }
        $user->setName($data['name']);
        $user->setLastname($data['lastname']);
        $user->setEmail($data['email']);
        $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);
        $user->setBirthday(new \DateTime($data['birthday']));
        $this->repository->save($user, true);
        return new JsonResponse(
            ['message' => 'User updated'],
            JsonResponse::HTTP_OK
        );
    }

    #[Route('/{id}', name: 'users.delete', methods:['DELETE'])]
    public function deleteUser(
        int $id
        ): JsonResponse
    {
        $user =  $this->repository->find($id);
        if (!$user) {
            return new JsonResponse(
                ['message' => 'User not found'],
                JsonResponse::HTTP_NOT_FOUND
            );
        }
        $this->repository->remove($user, true);
        return new JsonResponse(
            ['message' => 'User deleted'],
            JsonResponse::HTTP_OK
        );
    }
}
