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

    #[Route('/api/absences/{studentId}', name: 'absence.getOne', methods:['GET'])]
    public function getAbsencesByStudentId(
        AbsenceRepository $repository,
        SerializerInterface $serializer,
        int $studentId
        ): JsonResponse
    {
        $absence =  $repository->findBy(['student' => $studentId]);
        $jsonAbsence = $serializer->serialize($absence, 'json',["groups" => "getAllAbsences"]);
        return new JsonResponse(    
            $jsonAbsence,
            Response::HTTP_OK, 
            [], 
            true
        );
    }

    #[Route('/api/absences/justified/{studentId}', name: 'absence.getAll', methods:['GET'])]
    public function getJustifiedAbsencesByStudentId(
        AbsenceRepository $repository,
        SerializerInterface $serializer,
        int $studentId
        ): JsonResponse
    {
        $absence =  $repository->findBy(['student' => $studentId, 'justified' => true]);
        $jsonAbsence = $serializer->serialize($absence, 'json',["groups" => "getAllAbsences"]);
        return new JsonResponse(    
            $jsonAbsence,
            Response::HTTP_OK, 
            [], 
            true
        );
    }

    #[Route('/api/absences/unjustified/{studentId}', name: 'absence.getAll', methods:['GET'])]
    public function getUnjustifiedAbsencesByStudentId(
        AbsenceRepository $repository,
        SerializerInterface $serializer,
        int $studentId
        ): JsonResponse
    {
        $absence =  $repository->findBy(['student' => $studentId, 'justified' => false]);
        $jsonAbsence = $serializer->serialize($absence, 'json',["groups" => "getAllAbsences"]);
        return new JsonResponse(    
            $jsonAbsence,
            Response::HTTP_OK, 
            [], 
            true
        );
    }

    #[Route('/api/absences/{studentId}/{semesterId}', name: 'absence.getAbsenceByStudent', methods:['GET'])]
    public function getAbsencesByStudentAndSemester(
        AbsenceRepository $repository,
        SerializerInterface $serializer,
        int $studentId,
        int $semesterId
        ): JsonResponse
    {
        $absence =  $repository->findBy(['student' => $studentId, 'semester' => $semesterId]);
        $jsonAbsence = $serializer->serialize($absence, 'json',["groups" => "getAllAbsences"]);
        return new JsonResponse(    
            $jsonAbsence,
            Response::HTTP_OK, 
            [], 
            true
        );
    }

    #[Route('/api/absences/{studentId}/{semesterId}/justified', name: 'absence.getAbsenceByStudent', methods:['GET'])]
    public function getJustifiedAbsencesByStudentAndSemester(
        AbsenceRepository $repository,
        SerializerInterface $serializer,
        int $studentId,
        int $semesterId
        ): JsonResponse
    {
        $absence =  $repository->findBy(['student' => $studentId, 'semester' => $semesterId, 'justified' => true]);
        $jsonAbsence = $serializer->serialize($absence, 'json',["groups" => "getAllAbsences"]);
        return new JsonResponse(    
            $jsonAbsence,
            Response::HTTP_OK, 
            [], 
            true
        );
    }

    #[Route('/api/absences/{studentId}/{semesterId}/unjustified', name: 'absence.getAbsenceByStudent', methods:['GET'])]
    public function getUnjustifiedAbsencesByStudentAndSemester(
        AbsenceRepository $repository,
        SerializerInterface $serializer,
        int $studentId,
        int $semesterId
        ): JsonResponse
    {
        $absence =  $repository->findBy(['student' => $studentId, 'semester' => $semesterId, 'justified' => false]);
        $jsonAbsence = $serializer->serialize($absence, 'json',["groups" => "getAllAbsences"]);
        return new JsonResponse(    
            $jsonAbsence,
            Response::HTTP_OK, 
            [], 
            true
        );
    }

    #[Route('/api/absences/{semesterId}', name: 'absence.getAbsenceBySemester', methods:['GET'])]
    public function getAbsencesBySemester(
        AbsenceRepository $repository,
        SerializerInterface $serializer,
        int $semesterId
        ): JsonResponse
    {
        $absence =  $repository->findBy(['semester' => $semesterId]);
        $jsonAbsence = $serializer->serialize($absence, 'json',["groups" => "getAllAbsences"]);
        return new JsonResponse(    
            $jsonAbsence,
            Response::HTTP_OK, 
            [], 
            true
        );
    }

    #[Route('/api/absences/{semesterId}/unjustified', name: 'absence.getAbsenceBySemester', methods:['GET'])]
    public function getUnjustifiedAbsencesBySemester(
        AbsenceRepository $repository,
        SerializerInterface $serializer,
        int $semesterId
        ): JsonResponse
    {
        $absence =  $repository->findBy(['semester' => $semesterId, 'justified' => false]);
        $jsonAbsence = $serializer->serialize($absence, 'json',["groups" => "getAllAbsences"]);
        return new JsonResponse(    
            $jsonAbsence,
            Response::HTTP_OK, 
            [], 
            true
        );
    }

    #[Route('/api/absences/{absencesId}/{studentId}', name: 'absence.updateAbsence', methods:['PUT'])]
    public function updateAbsence(
        AbsenceRepository $repository,
        SerializerInterface $serializer,
        Request $request,
        int $absencesId,
        int $studentId
        ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $absence = $repository->findOneBy(['id' => $absencesId, 'student' => $studentId]);
        $absence->setJustified($data['justified']);
        $absence->setJustification($data['justification']);
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($absence);
        $entityManager->flush();
        return new JsonResponse(    
            $serializer->serialize($absence, 'json',["groups" => "getAllAbsences"]),
            Response::HTTP_OK, 
            [], 
            true
        );
    }

    #[Route('/api/absences/{studentId}', name: 'absence.createAbsenceForStudent', methods:['POST'])]
    public function createAbsenceForStudent(
        AbsenceRepository $repository,
        SerializerInterface $serializer,
        Request $request,
        EntityManagerInterface $em,
        int $studentId
        ): JsonResponse
    {
        $data = $request->getContent();
        $absence = $serializer->deserialize($data, Absence::class, 'json');
        $absence->setStudent($studentId);
        $em->persist($absence);
        $em->flush();
        return new JsonResponse(    
            $serializer->serialize($absence, 'json',["groups" => "getAllAbsences"]),
            Response::HTTP_OK, 
            [], 
            true
        );
    }

}
