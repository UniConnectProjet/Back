<?php

namespace App\Controller;

use App\Entity\Classe;
use App\Entity\Student;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ClasseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;


class ClasseController extends AbstractController
{
    #[Route('/classe', name: 'app_classe')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/ClasseController.php',
        ]);
    }
    
    #[Route('/api/classes', name: 'classe.getAll', methods:['GET'])]
    public function getAllClasses(
        ClasseRepository $repository,
        SerializerInterface $serializer
        ): JsonResponse
    {
        $classe =  $repository->findAll();
        $jsonClasse = $serializer->serialize($classe, 'json',["groups" => "getAllClasses"]);
        return new JsonResponse(    
            $jsonClasse,
            JsonResponse::HTTP_OK, 
            [], 
            true
        );
    }

    #[Route('/api/classes/{classeId}', name: 'classe.getAllStudentsByClass', methods:['GET'])]
    public function getAllStudentsByClass(
        ClasseRepository $repository,
        SerializerInterface $serializer,
        int $classeId
        ): JsonResponse
    {
        $classe =  $repository->find($classeId);
        $jsonClasse = $serializer->serialize($classe, 'json',["groups" => "getStudentsByClassId"]);
        return new JsonResponse(    
            $jsonClasse,
            JsonResponse::HTTP_OK, 
            [], 
            true
        );
    }

    #[Route('/api/classes/{classId}/students/{studentId}', name: 'class.addStudent', methods:['POST'])]
    public function addStudentToClass(
        int $classId,
        int $studentId,
        EntityManagerInterface $em
    ): JsonResponse {
        $class = $em->getRepository(Classe::class)->find($classId);
        if (!$class) {
            return new JsonResponse(['error' => 'Class not found'], Response::HTTP_NOT_FOUND);
        }

        $student = $em->getRepository(Student::class)->find($studentId);
        if (!$student) {
            return new JsonResponse(['error' => 'Student not found'], Response::HTTP_NOT_FOUND);
        }

        $class->addStudent($student);
        $em->persist($class);
        $em->flush();

        return new JsonResponse(['message' => 'Student added to class successfully'], JsonResponse::HTTP_OK);
    }
}
