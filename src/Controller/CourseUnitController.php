<?php

namespace App\Controller;

use App\Repository\CourseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\CourseUnitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Request;

class CourseUnitController extends AbstractController
{
    /**
     * Page d'accueil du contrôleur.
     * 
     * @Route("/course/unit", name="app_course_unit", methods={"GET"})
     * 
     * @return JsonResponse Message d'accueil.
     */
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/CourseUnitController.php',
        ]);
    }

    /**
     * Récupère toutes les unités d'enseignement (UE).
     * 
     * @Route("/api/course/units", name="courseUnit.getAll", methods={"GET"})
     * 
     * @param CourseUnitRepository $repository Le repository des unités d'enseignement.
     * @param SerializerInterface $serializer Le sérialiseur pour transformer les données en JSON.
     * 
     * @return JsonResponse La liste de toutes les UE.
     */
    #[Route('/api/course/units', name: 'courseUnit.getAll', methods:['GET'])]
    public function getAllCourseUnits(
        CourseUnitRepository $repository,
        SerializerInterface $serializer
    ): JsonResponse {
        $courseUnits = $repository->findAll();
        $jsonCourseUnits = $serializer->serialize($courseUnits, 'json', ["groups" => "getAllCourseUnits"]);

        return new JsonResponse(
            $jsonCourseUnits,
            JsonResponse::HTTP_OK,
            [],
            true
        );
    }

    /**
     * Récupère une unité d'enseignement (UE) spécifique.
     * 
     * @Route("/api/course/unit/{courseUnitId}", name="courseUnit.getById", methods={"GET"})
     * 
     * @param CourseUnitRepository $repository Le repository des unités d'enseignement.
     * @param SerializerInterface $serializer Le sérialiseur pour transformer les données en JSON.
     * @param int $courseUnitId L'identifiant de l'UE.
     * 
     * @return JsonResponse L'UE demandée ou une erreur 404.
     */
    #[Route('/api/course/unit/{courseUnitId}', name: 'courseUnit.getById', methods:['GET'])]
    public function getCourseUnit(
        CourseUnitRepository $repository,
        SerializerInterface $serializer,
        int $courseUnitId
    ): JsonResponse {
        $courseUnit = $repository->find($courseUnitId);

        if (!$courseUnit) {
            return new JsonResponse(['error' => 'Course Unit not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $jsonCourseUnit = $serializer->serialize($courseUnit, 'json', ["groups" => "getAllCourseUnits"]);

        return new JsonResponse(
            $jsonCourseUnit,
            JsonResponse::HTTP_OK,
            [],
            true
        );
    }

    /**
     * Récupère les modules (Courses) d'une unité d'enseignement (UE).
     * 
     * @Route("/api/course/unit/{courseUnitId}/modules", name="courseUnit.getCourse", methods={"GET"})
     * 
     * @param CourseUnitRepository $repository Le repository des unités d'enseignement.
     * @param SerializerInterface $serializer Le sérialiseur pour transformer les données en JSON.
     * @param int $courseUnitId L'identifiant de l'UE.
     * 
     * @return JsonResponse Les modules associés à l'UE.
     */
    #[Route('/api/course/unit/{courseUnitId}/modules', name: 'courseUnit.getCourse', methods:['GET'])]
    public function getCourseByCourseUnit(
        CourseUnitRepository $repository,
        SerializerInterface $serializer,
        int $courseUnitId
    ): JsonResponse {
        $courseUnit = $repository->find($courseUnitId);

        if (!$courseUnit) {
            return new JsonResponse(['error' => 'Course Unit not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $modules = $courseUnit->getCourses(); // Suppose que l'entité CourseUnit a une relation "courses"
        $jsonModules = $serializer->serialize($modules, 'json', ["groups" => "getAllModules"]);

        return new JsonResponse(
            $jsonModules,
            JsonResponse::HTTP_OK,
            [],
            true
        );
    }

    /**
     * Récupère les UE d'un étudiant.
     * 
     * @Route("/api/course/units/student/{studentId}", name="courseUnit.getByStudent", methods={"GET"})
     * 
     * @param CourseUnitRepository $repository Le repository des unités d'enseignement.
     * @param SerializerInterface $serializer Le sérialiseur pour transformer les données en JSON.
     * @param int $studentId L'identifiant de l'étudiant.
     * 
     * @return JsonResponse Les UE associées à l'étudiant.
     */
    #[Route('/api/course/units/student/{studentId}', name: 'courseUnit.getByStudent', methods:['GET'])]
    public function getCourseUnitsByStudent(
        CourseUnitRepository $repository,
        SerializerInterface $serializer,
        int $studentId
    ): JsonResponse {
        $courseUnits = $repository->findByStudent($studentId); // Suppose que cette méthode existe dans le repository
        $jsonCourseUnits = $serializer->serialize($courseUnits, 'json', ["groups" => "getAllCourseUnits"]);

        return new JsonResponse(
            $jsonCourseUnits,
            JsonResponse::HTTP_OK,
            [],
            true
        );
    }

    /**
     * Associe un module (Course) à une unité d'enseignement (UE).
     * 
     * @Route("/api/course/unit/{courseUnitId}/addCourse/{courseId}", name="courseUnit.addCourse", methods={"POST"})
     * 
     * @param CourseUnitRepository $courseUnitRepository Le repository des unités d'enseignement.
     * @param CourseRepository $courseRepository Le repository des modules.
     * @param EntityManagerInterface $entityManager Le gestionnaire d'entités Doctrine.
     * @param int $courseUnitId L'identifiant de l'UE.
     * @param int $courseId L'identifiant du module.
     * 
     * @return JsonResponse Confirmation ou erreur si l'UE ou le module n'existe pas.
     */
    #[Route('/api/course/unit/{courseUnitId}/addCourse/{courseId}', name: 'courseUnit.addCourse', methods:['POST'])]
    public function addCourseToCourseUnit(
        CourseUnitRepository $courseUnitRepository,
        CourseRepository $courseRepository,
        EntityManagerInterface $entityManager,
        int $courseUnitId,
        int $courseId
    ): JsonResponse {
        // Récupérer l'UE
        $courseUnit = $courseUnitRepository->find($courseUnitId);
        if (!$courseUnit) {
            return new JsonResponse(['error' => 'Course Unit not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        // Récupérer le module
        $course = $courseRepository->find($courseId);
        if (!$course) {
            return new JsonResponse(['error' => 'Course not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        // Associer le module à l'UE
        $course->setCourseUnit($courseUnit); // Suppose que l'entité Course a une méthode setCourseUnit()
        $entityManager->persist($course);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Module successfully associated to Course Unit'], JsonResponse::HTTP_OK);
    }

    /**
     * Supprime un module (Course) d'une unité d'enseignement (UE).
     * 
     * @Route("/api/course/unit/{courseUnitId}/removeModule/{courseId}", name="courseUnit.removeCourse", methods={"DELETE"})
     * 
     * @param CourseUnitRepository $courseUnitRepository Le repository des unités d'enseignement.
     * @param CourseRepository $courseRepository Le repository des modules.
     * @param EntityManagerInterface $entityManager Le gestionnaire d'entités Doctrine.
     * @param int $courseUnitId L'identifiant de l'UE.
     * @param int $courseId L'identifiant du module.
     * 
     * @return JsonResponse Confirmation ou erreur si l'UE ou le module n'existe pas.
     */
    #[Route('/api/course/unit/{courseUnitId}/removeModule/{courseId}', name: 'courseUnit.removeCourse', methods:['DELETE'])]
    public function removeCourseFromCourseUnit(
        CourseUnitRepository $courseUnitRepository,
        CourseRepository $courseRepository,
        EntityManagerInterface $entityManager,
        int $courseUnitId,
        int $courseId
    ): JsonResponse {
        // Récupérer l'UE
        $courseUnit = $courseUnitRepository->find($courseUnitId);
        if (!$courseUnit) {
            return new JsonResponse(['error' => 'Course Unit not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        // Récupérer le module
        $course = $courseRepository->find($courseId);
        if (!$course) {
            return new JsonResponse(['error' => 'Course not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        // Vérifier si le module est lié à l'UE
        if ($course->getCourseUnit() !== $courseUnit) {
            return new JsonResponse(['error' => 'Module is not associated with this Course Unit'], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Supprimer l'association entre le module et l'UE
        $course->setCourseUnit(null);
        $entityManager->persist($course);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Module successfully removed from Course Unit'], JsonResponse::HTTP_OK);
    }

    /**
     * Calcule la moyenne des moyennes des modules associés à une UE et la stocke dans l'UE.
     * 
     * @Route("/api/course/unit/{courseUnitId}/calculateAverage", name="courseUnit.calculateAverage", methods={"POST"})
     * 
     * @param CourseUnitRepository $courseUnitRepository Le repository des unités d'enseignement.
     * @param EntityManagerInterface $entityManager Le gestionnaire d'entités Doctrine.
     * @param int $courseUnitId L'identifiant de l'UE.
     * 
     * @return JsonResponse La nouvelle moyenne ou une erreur si l'UE n'existe pas ou n'a pas de modules.
     */
    public function calculateAverageForCourseUnit(
        CourseUnitRepository $courseUnitRepository,
        EntityManagerInterface $entityManager,
        int $courseUnitId
    ): JsonResponse {
        // Récupérer l'UE
        $courseUnit = $courseUnitRepository->find($courseUnitId);
        if (!$courseUnit) {
            return new JsonResponse(['error' => 'Course Unit not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        // Récupérer les modules associés à l'UE
        $courses = $courseUnit->getCourses();
        if ($courses->isEmpty()) {
            return new JsonResponse(['error' => 'No modules found for this Course Unit'], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Calculer la moyenne des moyennes des modules
        $totalAverage = 0;
        $countCourses = 0;

        foreach ($courses as $course) {
            if ($course->getAverage() !== null) { // Vérifie que le champ average est défini
                $totalAverage += $course->getAverage();
                $countCourses++;
            }
        }

        if ($countCourses === 0) {
            return new JsonResponse(['error' => 'No averages found in the modules for this Course Unit'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $overallAverage = $totalAverage / $countCourses;

        // Mettre à jour et enregistrer la moyenne dans l'UE
        $courseUnit->setAverageScore($overallAverage);
        $entityManager->persist($courseUnit);
        $entityManager->flush();

        return new JsonResponse(
            ['courseUnitId' => $courseUnitId, 'averageScore' => $overallAverage],
            JsonResponse::HTTP_OK
        );
    }
}