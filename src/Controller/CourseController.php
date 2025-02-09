<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\CourseRepository;

class CourseController extends AbstractController
{
    #[Route('/course', name: 'app_course')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/CourseController.php',
        ]);
    }

    #[Route('/api/courses', name: 'course.getAll', methods:['GET'])]
    public function getAllCourses(
        CourseRepository $repository,
        SerializerInterface $serializer
        ): JsonResponse
    {
        $course =  $repository->findAll();
        $jsonCourse = $serializer->serialize($course, 'json',["groups" => "getAllCourses"]);
        return new JsonResponse(    
            $jsonCourse,
            Response::HTTP_OK, 
            [], 
            true
        );
    }

    #[Route('/api/course/{studentId}', name: 'course.getOne', methods:['GET'])]
    public function getCourseByStudentId(
        CourseRepository $repository,
        SerializerInterface $serializer,
        int $studentId
        ): JsonResponse
    {
        $course =  $repository->findBy(['student' => $studentId]);
        $jsonCourse = $serializer->serialize($course, 'json',["groups" => "getAllCourses"]);
        return new JsonResponse(    
            $jsonCourse,
            Response::HTTP_OK, 
            [], 
            true
        );
    }

    #[Route('/api/course/{courseUnitId}', name: 'course.getOne', methods:['GET'])]
    public function getCourseByCourseUnitId(
        CourseRepository $repository,
        SerializerInterface $serializer,
        int $courseUnitId
        ): JsonResponse
    {
        $course =  $repository->findBy(['courseUnit' => $courseUnitId]);
        $jsonCourse = $serializer->serialize($course, 'json',["groups" => "getAllCourses"]);
        return new JsonResponse(    
            $jsonCourse,
            Response::HTTP_OK, 
            [], 
            true
        );
    }

    #[Route('/api/course/{semesterId}', name: 'course.getOne', methods:['GET'])]
    public function getCourseBySemesterId(
        CourseRepository $repository,
        SerializerInterface $serializer,
        int $semesterId
        ): JsonResponse
    {
        $course =  $repository->findBy(['semester' => $semesterId]);
        $jsonCourse = $serializer->serialize($course, 'json',["groups" => "getAllCourses"]);
        return new JsonResponse(    
            $jsonCourse,
            Response::HTTP_OK, 
            [], 
            true
        );
    }

    #[Route('/api/course/{courseUnitId}/{semesterId}', name: 'course.getOne', methods:['GET'])]
    public function getCourseByCourseUnitIdAndSemesterId(
        CourseRepository $repository,
        SerializerInterface $serializer,
        int $courseUnitId,
        int $semesterId
        ): JsonResponse
    {
        $course =  $repository->findBy(['courseUnit' => $courseUnitId, 'semester' => $semesterId]);
        $jsonCourse = $serializer->serialize($course, 'json',["groups" => "getAllCourses"]);
        return new JsonResponse(    
            $jsonCourse,
            Response::HTTP_OK, 
            [], 
            true
        );
    }

    #[Route('/api/course/{studentId}/{courseUnitId}', name: 'course.getOne', methods:['GET'])]
    public function getCourseByStudentIdAndCourseUnitId(
        CourseRepository $repository,
        SerializerInterface $serializer,
        int $studentId,
        int $courseUnitId
        ): JsonResponse
    {
        $course =  $repository->findBy(['student' => $studentId, 'courseUnit' => $courseUnitId]);
        $jsonCourse = $serializer->serialize($course, 'json',["groups" => "getAllCourses"]);
        return new JsonResponse(    
            $jsonCourse,
            Response::HTTP_OK, 
            [], 
            true
        );
    }

    #[Route('/api/course/{studentId}/{semesterId}', name: 'course.getOne', methods:['GET'])]
    public function getCourseByStudentIdAndSemesterId(
        CourseRepository $repository,
        SerializerInterface $serializer,
        int $studentId,
        int $semesterId
        ): JsonResponse
    {
        $course =  $repository->findBy(['student' => $studentId, 'semester' => $semesterId]);
        $jsonCourse = $serializer->serialize($course, 'json',["groups" => "getAllCourses"]);
        return new JsonResponse(    
            $jsonCourse,
            Response::HTTP_OK, 
            [], 
            true
        );
    }

    #[Route('/api/course/average', name: 'course.calculate', methods:['PUT'])]
    public function calculateAverage(
        CourseRepository $repository,
        SerializerInterface $serializer
        ): JsonResponse
    {
        $course =  $repository->findAll();
        $average = 0;
        foreach ($course as $c) {
            $average += $c->getAverage();
        }
        $average = $average / count($course);
        return new JsonResponse(    
            $average,
            Response::HTTP_OK, 
            [], 
            true
        );
    }

    #[Route('/api/course/{id}', name: 'course.delete', methods:['DELETE'])]
    public function deleteCourse(
        CourseRepository $repository,
        int $id
        ): JsonResponse
    {
        $course =  $repository->find($id);
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($course);
        $entityManager->flush();
        return new JsonResponse(    
            null,
            Response::HTTP_NO_CONTENT, 
            [], 
            true
        );
    }

    #[Route('/api/course', name: 'course.create', methods:['POST'])]
    public function createCourse(
        Request $request,
        SerializerInterface $serializer
        ): JsonResponse
    {
        $data = $request->getContent();
        $course = $serializer->deserialize($data, Course::class, 'json');
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($course);
        $entityManager->flush();
        return new JsonResponse(    
            null,
            Response::HTTP_CREATED, 
            [], 
            true
        );
    }

    #[Route('/api/course/{id}', name: 'course.update', methods:['PUT'])]
    public function updateCourse(
        CourseRepository $repository,
        Request $request,
        SerializerInterface $serializer,
        int $id
        ): JsonResponse
    {
        $course =  $repository->find($id);
        $data = $request->getContent();
        $serializer->deserialize($data, Course::class, 'json', ['object_to_populate' => $course]);
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($course);
        $entityManager->flush();
        return new JsonResponse(    
            null,
            Response::HTTP_OK, 
            [], 
            true
        );
    }
}
