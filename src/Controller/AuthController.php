<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AuthController extends AbstractController
{
    #[Route('/api/protected', name: 'api_protected', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function protectedRoute()
    {
        return new JsonResponse([
            'message' => 'You have access to this protected route!',
        ]);
    }
}