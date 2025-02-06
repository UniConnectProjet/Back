<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class AbsenceController extends AbstractController
{
    #[Route('/absence', name: 'app_absence')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/AbsenceController.php',
        ]);
    }

    #[Route('/api/absences', name: 'absence.getAll', methods:['GET'])]
    public function getAllAbsences(
        AbsenceRepository $repository,
        SerializerInterface $serializer
        ): JsonResponse
    {
        $absence =  $repository->findAll();
        $jsonAbsence = $serializer->serialize($absence, 'json',["groups" => "getAllAbsences"]);
        return new JsonResponse(    
            $jsonAbsence,
            Response::HTTP_OK, 
            [], 
            true
        );
    }
}
