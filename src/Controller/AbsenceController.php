<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\AbsenceRepository;
use App\Entity\Absence;
use App\Repository\SemesterRepository;
use App\Repository\StudentRepository;
use Symfony\Component\Serializer\SerializerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

#[Route('/api/absences')]
class AbsenceController extends AbstractController
{
    /**
     * Récupère les absences avec des filtres dynamiques.
     * 
     * @Route("/", name="absence.getAll", methods={"GET"})
     * 
     * @param AbsenceRepository $repository Le repository des absences.
     * @param SerializerInterface $serializer Le sérialiseur pour transformer les données en JSON.
     * @param Request $request La requête HTTP contenant les filtres.
     * 
     * @return JsonResponse La liste des absences filtrées.
     */
    #[Route('/', name: 'absence.getAll', methods: ['GET'])]
    public function getAbsences(
        AbsenceRepository $repository,
        SerializerInterface $serializer,
        Request $request
    ): JsonResponse {
        $param = $request->query->all();

        if (isset($param['studentId'])) {
            $param['student'] = $param['studentId'];
            unset($param['studentId']);
        }
        if (isset($param['semesterId'])) {
            $param['semester'] = $param['semesterId'];
            unset($param['semesterId']);
        }

        $absences = $repository->findBy($param);
        $jsonAbsences = $serializer->serialize($absences, 'json', ["groups" => "getAllAbsences"]);

        return new JsonResponse(
            $jsonAbsences,
            JsonResponse::HTTP_OK,
            [],
            true
        );
    }

    /**
     * Récupère toutes les absences pour un semestre donné.
     * 
     * @Route("/semester/{semesterId}", name="absence.getBySemester", methods={"GET"})
     * 
     * @param AbsenceRepository $repository Le repository des absences.
     * @param SerializerInterface $serializer Le sérialiseur pour transformer les données en JSON.
     * @param int $semesterId L'identifiant du semestre.
     * 
     * @return JsonResponse La liste des absences pour le semestre.
     */
    #[Route('/semester/{semesterId}', name: 'absence.getBySemester', methods: ['GET'])]
    public function getAbsencesBySemester(
        AbsenceRepository $repository,
        SerializerInterface $serializer,
        int $semesterId
    ): JsonResponse {
        $absences = $repository->findBy(['semester' => $semesterId]);
        $jsonAbsences = $serializer->serialize($absences, 'json', ["groups" => "getAllAbsences"]);

        return new JsonResponse(
            $jsonAbsences,
            JsonResponse::HTTP_OK,
            [],
            true
        );
    }

    /**
     * Met à jour une absence donnée.
     * 
     * @Route("/{absenceId}", name="absence.update", methods={"PUT"})
     * 
     * @param AbsenceRepository $repository Le repository des absences.
     * @param SerializerInterface $serializer Le sérialiseur pour transformer les données en JSON.
     * @param Request $request La requête contenant les données de mise à jour.
     * @param EntityManagerInterface $em Le gestionnaire d'entités Doctrine.
     * @param int $absenceId L'identifiant de l'absence à mettre à jour.
     * 
     * @return JsonResponse L'absence mise à jour.
     */

    #[Route('/{absenceId}', name: 'absence.update', methods: ['PUT'])]
    public function updateAbsence(
        AbsenceRepository $repository,
        SerializerInterface $serializer,
        Request $request,
        EntityManagerInterface $em,
        int $absenceId
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $absence = $repository->find($absenceId);
        if (!$absence) {
            return new JsonResponse(['error' => 'Absence not found'], Response::HTTP_NOT_FOUND);
        }

        if (isset($data['justified'])) {
            $absence->setJustified($data['justified']);
        }
        if (isset($data['justification'])) {
            $absence->setJustification($data['justification']);
        }

        $em->persist($absence);
        $em->flush();

        $jsonAbsence = $serializer->serialize($absence, 'json', ["groups" => "getAllAbsences"]);
        return new JsonResponse(
            $jsonAbsence,
            JsonResponse::HTTP_OK,
            [],
            true
        );
    }

    /**
     * Crée une absence pour un étudiant donné.
     * 
     * @Route("/student/{studentId}", name="absence.create", methods={"POST"})
     * 
     * @param StudentRepository $studentRepository Le repository des étudiants.
     * @param SerializerInterface $serializer Le sérialiseur pour transformer les données en JSON.
     * @param Request $request La requête contenant les données de l'absence.
     * @param EntityManagerInterface $em Le gestionnaire d'entités Doctrine.
     * @param int $studentId L'identifiant de l'étudiant.
     * 
     * @return JsonResponse L'absence nouvellement créée.
     */
    
    
    #[Route('/student/{studentId}/semester/{semesterId}', name: 'absence.createForStudent', methods: ['POST'])]
    public function createAbsenceForStudent(
        StudentRepository $studentRepository,
        SemesterRepository $semesterRepository,
        SerializerInterface $serializer,
        Request $request,
        EntityManagerInterface $em,
        int $studentId,
        int $semesterId
    ): JsonResponse {
        $student = $studentRepository->find($studentId);
        if (!$student) {
            return new JsonResponse(['error' => 'Student not found'], Response::HTTP_NOT_FOUND);
        }

        
        $semester = $semesterRepository->find($semesterId);
        if (!$semester) {
             return new JsonResponse(['error' => 'Semester not found'], Response::HTTP_NOT_FOUND);
        }

        $data = $request->getContent();
        $absence = $serializer->deserialize($data, Absence::class, 'json');
        $absence->setStudent($student);
        $absence->setSemester($semester);

        $em->persist($absence);
        $em->flush();

        $jsonAbsence = $serializer->serialize($absence, 'json', ["groups" => "getAllAbsences"]);
        return new JsonResponse(
            $jsonAbsence,
            JsonResponse::HTTP_OK,
            [],
            true
        );
    }

    #[Route('/{absenceId}', name: 'absence.deleteAbsence', methods: ['DELETE'])]
    public function deleteAbsence(
        AbsenceRepository $repository,
        EntityManagerInterface $em,
        int $absenceId
    ): JsonResponse {
        $absence = $repository->find($absenceId);
        if (!$absence) {
            return new JsonResponse(['error' => 'Absence not found'], Response::HTTP_NOT_FOUND);
        }

        $em->remove($absence);
        $em->flush();

        return new JsonResponse(['message' => 'Absence deleted successfully'], JsonResponse::HTTP_OK);
    }
}