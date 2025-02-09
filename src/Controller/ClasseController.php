<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

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
        $jsonClasse = $serializer->serialize($course, 'json',["groups" => "getAllClasses"]);
        return new JsonResponse(    
            $jsonClasse,
            Response::HTTP_OK, 
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
        $jsonClasse = $serializer->serialize($classe, 'json',["groups" => "getAllClasses"]);
        return new JsonResponse(    
            $jsonClasse,
            Response::HTTP_OK, 
            [], 
            true
        );
    }

    #[Route('/api/classes/{studentId}', name: 'classe.addStudent', methods:['POST'])]
    public function addStudentToClass(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em
        ): JsonResponse
    {
        $data = $request->getContent();
        $student = $serializer->deserialize($data, Student::class, 'json');
        $em->persist($student);
        $em->flush();
        return new JsonResponse(
            $serializer->serialize($student, 'json'),
            Response::HTTP_CREATED,
            [],
            true
        );
    }

    #[Route('/api/classes/{classeId}/{studentId}', name: 'classe.removeStudent', methods:['DELETE'])]
    public function removeStudentFromClass(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em
        ): JsonResponse
    {
        $data = $request->getContent();
        $student = $serializer->deserialize($data, Student::class, 'json');
        $em->remove($student);
        $em->flush();
        return new JsonResponse(
            $serializer->serialize($student, 'json'),
            Response::HTTP_CREATED,
            [],
            true
        );
    }

    #[Route('/api/classes/{classeId}', name: 'classe.update', methods:['PUT'])]
    public function updateClasse(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        int $classeId
        ): JsonResponse
    {
        $data = $request->getContent();
        $classe = $serializer->deserialize($data, Classe::class, 'json');
        $classe->setClasse($classeId);
        $em->persist($classe);
        $em->flush();
        return new JsonResponse(
            'Classe updated',
            Response::HTTP_OK,
            [],
            true
        );
    }

    #[Route('/api/classes/{classeId}', name: 'classe.delete', methods:['DELETE'])]
    public function deleteClasse(
        ClasseRepository $repository,
        EntityManagerInterface $em,
        int $classeId
        ): JsonResponse
    {
        $classe = $repository->find($classeId);
        $em->remove($classe);
        $em->flush();
        return new JsonResponse(
            'Classe deleted',
            Response::HTTP_OK,
            [],
            true
        );
    }
}
