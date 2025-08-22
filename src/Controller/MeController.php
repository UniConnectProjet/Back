<?php
namespace App\Controller;

use App\Repository\StudentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
final class MeController extends AbstractController
{
    #[Route('/me', name: 'api_me', methods: ['GET'])]
    public function me(StudentRepository $students): JsonResponse
    {
        $u = $this->getUser();
        if (!$u) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        $s = $students->findOneBy(['user' => $u]);

        // tolérant aux variations de getters
        $first = method_exists($u, 'getName')      ? $u->getName()
            : (method_exists($u, 'getFirstName') ? $u->getFirstName()
            : (method_exists($u, 'getFirstname') ? $u->getFirstname() : null));

        $last  = method_exists($u, 'getLastname')  ? $u->getLastname()
            : (method_exists($u, 'getLastName')  ? $u->getLastName()
            : (method_exists($u, 'getLastname')  ? $u->getLastname() : null));

        $email = method_exists($u, 'getEmail') ? $u->getEmail() : $u->getUserIdentifier();

        return $this->json([
            'id'        => $u->getId(),
            'email'     => $email,
            'roles'     => $u->getRoles(),
            'firstName' => $first,
            'lastName'  => $last,
            'student'   => $s ? ['id' => $s->getId()] : null,
        ]);
    }

}
