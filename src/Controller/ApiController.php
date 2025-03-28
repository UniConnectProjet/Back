<?php

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ApiController extends AbstractController
{
    #[Route('/api/ping', name: 'api_ping', methods: ['GET'])]
    public function ping(): JsonResponse
    {
        return new JsonResponse([
            'message' => 'pong 🏓',
            'status' => 'ok'
        ]);
    }
}
