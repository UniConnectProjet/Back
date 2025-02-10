<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\CourseRepository;
use App\Entity\Course;
use Symfony\Component\Serializer\SerializerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

class CourseController extends AbstractController
{
    /**
     * Page d'accueil du contrôleur.
     * 
     * @Route("/course", name="app_course", methods={"GET"})
     * 
     * @return JsonResponse Message d'accueil.
     */
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/CourseController.php',
        ]);
    }

    /**
     * Récupère tous les modules.
     * 
     * @Route("/api/courses", name="course.getAll", methods={"GET"})
     * 
     * @param CourseRepository $repository Le repository des modules.
     * @param SerializerInterface $serializer Le sérialiseur pour transformer les données en JSON.
     * 
     * @return JsonResponse La liste de tous les modules.
     */
    #[Route('/api/courses', name: 'course.getAll', methods:['GET'])]
    public function getAllCourses(
        CourseRepository $repository,
        SerializerInterface $serializer
    ): JsonResponse {
        $courses = $repository->findAll();
        $jsonCourses = $serializer->serialize($courses, 'json', ["groups" => "getAllCourses"]);

        return new JsonResponse(
            $jsonCourses,
            Response::HTTP_OK,
            [],
            true
        );
    }

    /**
     * Récupère les modules liés à un étudiant.
     * 
     * @Route("/api/course/student/{studentId}", name="course.getByStudent", methods={"GET"})
     * 
     * @param CourseRepository $repository Le repository des modules.
     * @param SerializerInterface $serializer Le sérialiseur pour transformer les données en JSON.
     * @param int $studentId L'identifiant de l'étudiant.
     * 
     * @return JsonResponse La liste des modules de l'étudiant.
     */
    #[Route('/api/course/student/{studentId}', name: 'course.getByStudent', methods:['GET'])]
    public function getCourseByStudentId(
        CourseRepository $repository,
        SerializerInterface $serializer,
        int $studentId
    ): JsonResponse {
        $courses = $repository->findBy(['student' => $studentId]);
        $jsonCourses = $serializer->serialize($courses, 'json', ["groups" => "getAllCourses"]);

        return new JsonResponse(
            $jsonCourses,
            Response::HTTP_OK,
            [],
            true
        );
    }

    /**
     * Récupère les modules liés à une unité d'enseignement.
     * 
     * @Route("/api/course/courseUnit/{courseUnitId}", name="course.getByCourseUnit", methods={"GET"})
     * 
     * @param CourseRepository $repository Le repository des modules.
     * @param SerializerInterface $serializer Le sérialiseur pour transformer les données en JSON.
     * @param int $courseUnitId L'identifiant de l'unité d'enseignement.
     * 
     * @return JsonResponse La liste des modules de l'unité.
     */
    #[Route('/api/course/courseUnit/{courseUnitId}', name: 'course.getByCourseUnit', methods:['GET'])]
    public function getCourseByCourseUnitId(
        CourseRepository $repository,
        SerializerInterface $serializer,
        int $courseUnitId
    ): JsonResponse {
        $courses = $repository->findBy(['courseUnit' => $courseUnitId]);
        $jsonCourses = $serializer->serialize($courses, 'json', ["groups" => "getAllCourses"]);

        return new JsonResponse(
            $jsonCourses,
            Response::HTTP_OK,
            [],
            true
        );
    }

    /**
     * Supprime un module.
     * 
     * @Route("/api/course/{id}", name="course.delete", methods={"DELETE"})
     * 
     * @param CourseRepository $repository Le repository des modules.
     * @param EntityManagerInterface $em Le gestionnaire d'entités Doctrine.
     * @param int $id L'identifiant du module.
     * 
     * @return JsonResponse Réponse vide ou erreur si le module n'existe pas.
     */
    #[Route('/api/course/{id}', name: 'course.delete', methods:['DELETE'])]
    public function deleteCourse(
        CourseRepository $repository,
        EntityManagerInterface $em,
        int $id
    ): JsonResponse {
        $course = $repository->find($id);
        if (!$course) {
            return new JsonResponse(['error' => 'Course not found'], Response::HTTP_NOT_FOUND);
        }

        $em->remove($course);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Crée un nouveau module.
     * 
     * @Route("/api/course", name="course.create", methods={"POST"})
     * 
     * @param Request $request La requête contenant les données du module.
     * @param SerializerInterface $serializer Le sérialiseur pour transformer les données en JSON.
     * @param EntityManagerInterface $em Le gestionnaire d'entités Doctrine.
     * 
     * @return JsonResponse Le statut de création du module.
     */
    #[Route('/api/course', name: 'course.create', methods:['POST'])]
    public function createCourse(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em
    ): JsonResponse {
        $data = $request->getContent();
        $course = $serializer->deserialize($data, Course::class, 'json');

        $em->persist($course);
        $em->flush();

        return new JsonResponse(
            $serializer->serialize($course, 'json', ["groups" => "getAllCourses"]),
            Response::HTTP_CREATED,
            [],
            true
        );
    }

    /**
     * Met à jour un module existant.
     * 
     * @Route("/api/course/{id}", name="course.update", methods={"PUT"})
     * 
     * @param CourseRepository $repository Le repository des modules.
     * @param Request $request La requête contenant les données à mettre à jour.
     * @param SerializerInterface $serializer Le sérialiseur pour transformer les données en JSON.
     * @param EntityManagerInterface $em Le gestionnaire d'entités Doctrine.
     * @param int $id L'identifiant du module.
     * 
     * @return JsonResponse Le statut de mise à jour du module.
     */
    #[Route('/api/course/{id}', name: 'course.update', methods:['PUT'])]
    public function updateCourse(
        CourseRepository $repository,
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        int $id
    ): JsonResponse {
        $course = $repository->find($id);
        if (!$course) {
            return new JsonResponse(['error' => 'Course not found'], Response::HTTP_NOT_FOUND);
        }

        $data = $request->getContent();
        $serializer->deserialize($data, Course::class, 'json', ['object_to_populate' => $course]);

        $em->persist($course);
        $em->flush();

        return new JsonResponse(
            $serializer->serialize($course, 'json', ["groups" => "getAllCourses"]),
            Response::HTTP_OK,
            [],
            true
        );
    }
}
