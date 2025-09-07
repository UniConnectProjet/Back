<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/mercure', name: 'api_mercure_')]
#[IsGranted('ROLE_USER')]
class MercureController extends AbstractController
{
    #[Route('/hub-url', name: 'hub_url', methods: ['GET'])]
    public function getHubUrl(): JsonResponse
    {
        // En production, cette URL devrait venir de la configuration
        $hubUrl = $_ENV['MERCURE_PUBLIC_URL'] ?? 'http://localhost:8000/.well-known/mercure';
        
        return $this->json([
            'hubUrl' => $hubUrl
        ]);
    }

    #[Route('/topics', name: 'topics', methods: ['GET'])]
    public function getTopics(): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        
        $topics = [
            "user/{$user->getId()}/notifications"
        ];

        return $this->json([
            'topics' => $topics
        ]);
    }
}
