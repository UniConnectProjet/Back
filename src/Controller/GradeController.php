<?php

namespace App\Controller;

use App\Entity\Student;
use App\Entity\Course;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\GradeRepository;
use App\Entity\Grade;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/api/grades')]
class GradeController extends AbstractController
{
    #[Route('/grade', name: 'app_grade')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/GradeController.php',
        ]);
    }

    #[Route('/', name: 'grade.getAll', methods:['GET'])]
    public function getAllGrades(
        GradeRepository $repository,
        SerializerInterface $serializer
        ): JsonResponse
    {
        $grade =  $repository->findAll();
        $jsonGrades = $serializer->serialize($grade, 'json',["groups" => "getAllGrades"]);
        return new JsonResponse(    
            $jsonGrades,
            Response::HTTP_OK, 
            [], 
            true
        );
    }

    #[Route('/student/{studentId}', name: 'grade.getByStudent', methods:['GET'])]
    public function getGradesByStudentId(
        GradeRepository $repository,
        SerializerInterface $serializer,
        int $studentId
        ): JsonResponse
    {
        $grade =  $repository->findBy(['student' => $studentId]);
        $jsonGrade = $serializer->serialize($grade, 'json',["groups" => "getAllGrades"]);
        return new JsonResponse(    
            $jsonGrade,
            Response::HTTP_OK, 
            [], 
            true
        );
    }

    #[Route('/course/{courseId}', name: 'grade.getByCourse', methods:['GET'])]
    public function getGradesByCourseId(
        GradeRepository $repository,
        SerializerInterface $serializer,
        int $courseId
        ): JsonResponse
    {
        $grade =  $repository->findBy(['course' => $courseId]);
        $jsonGrade = $serializer->serialize($grade, 'json',["groups" => "getAllGrades"]);
        return new JsonResponse(    
            $jsonGrade,
            Response::HTTP_OK, 
            [], 
            true
        );
    }

    #[Route('/semester/{semesterId}', name: 'grade.getBySemester', methods:['GET'])]
    public function getGradesBySemesterId(
        GradeRepository $repository,
        SerializerInterface $serializer,
        int $semesterId
        ): JsonResponse
    {
        $grade =  $repository->findBy(['semester' => $semesterId]);
        $jsonGrade = $serializer->serialize($grade, 'json',["groups" => "getAllGrades"]);
        return new JsonResponse(    
            $jsonGrade,
            Response::HTTP_OK, 
            [], 
            true
        );
    }

    #[Route('/student/{studentId}', name: 'grade.addForStudent', methods:['POST'])]
    public function addGradeForStudent(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        int $studentId
        ): JsonResponse
    {
        $data = $request->getContent();
        $grade = $serializer->deserialize($data, Grade::class, 'json');
        $student = $em->getRepository(Student::class)->find($studentId);
        
        if (!$student) {
            return new JsonResponse(['error' => 'Student not found'], Response::HTTP_NOT_FOUND);
        }
        $grade->setStudent($student);

        $em->persist($grade);
        $em->flush();
        return new JsonResponse(
            'Grade added',
            Response::HTTP_CREATED,
            [],
            true
        );
    }

    #[Route('/course/{courseId}', name: 'grade.addForCourse', methods:['POST'])]
    public function addGradeForCourse(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        int $courseId
        ): JsonResponse
    {
        $data = $request->getContent();
        $grade = $serializer->deserialize($data, Grade::class, 'json');
        $course = $em->getRepository(Course::class)->find($courseId);
        
        if (!$course) {
            return new JsonResponse(['error' => 'Course not found'], Response::HTTP_NOT_FOUND);
        }
        $grade->setCourse($course);

        $em->persist($grade);
        $em->flush();
        return new JsonResponse(
            'Grade added',
            Response::HTTP_CREATED,
            [],
            true
        );
    }

    #[Route('/{studentId}', name: 'grade.update', methods:['PUT'])]
    public function updateGrade(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        int $studentId
        ): JsonResponse
    {
        $data = $request->getContent();
        $grade = $serializer->deserialize($data, Grade::class, 'json');
        
        $student = $em->getRepository(Student::class)->find($studentId);
        if (!$student) {
            return new JsonResponse(['error' => 'Student not found'], Response::HTTP_NOT_FOUND);
        }
        $grade->setStudent($student);

        $em->persist($grade);
        $em->flush();
        return new JsonResponse(
            'Grade updated',
            Response::HTTP_OK,
            [],
            true
        );
    }

    #[Route('/{gradeId}', name: 'grade.delete', methods:['DELETE'])]
    public function deleteGrade(
        GradeRepository $repository,
        EntityManagerInterface $em,
        int $gradeId
        ): JsonResponse
    {
        $grade = $repository->find($gradeId);

        if (!$grade) {
            return new JsonResponse(['error' => 'Grade not found'], Response::HTTP_NOT_FOUND);
        }

        $em->remove($grade);
        $em->flush();
        return new JsonResponse(
            'Grade deleted',
            Response::HTTP_OK,
            [],
            true
        );
    }

}
