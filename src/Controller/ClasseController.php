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

#[Route('/api/classes')]
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
    
    #[Route('/', name: 'classe.getAll', methods:['GET'])]
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

    #[Route('/{classeId}', name: 'classe.getAllStudentsByClass', methods:['GET'])]
    public function getAllStudentsByClass(
        ClasseRepository $repository,
        SerializerInterface $serializer,
        int $classeId
        ): JsonResponse
    {
        $classe =  $repository->find($classeId);

        if (!$classe) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }
        $jsonClasse = $serializer->serialize($classe, 'json',["groups" => "getStudentsByClassId"]);
        return new JsonResponse(    
            $jsonClasse,
            JsonResponse::HTTP_OK, 
            [], 
            true
        );
    }

    #[Route('/{classId}/students/{studentId}', name: 'classes.add_student', methods: ['POST'])]
    public function addStudent(int $classId, int $studentId, EntityManagerInterface $em): Response
    {
        $class = $em->getRepository(Classe::class)->find($classId);
        if (!$class) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        $student = $em->getRepository(Student::class)->find($studentId);
        if (!$student) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        $class->addStudent($student);
        $em->flush();

        // ✅ 204 No Content, SANS body
        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
