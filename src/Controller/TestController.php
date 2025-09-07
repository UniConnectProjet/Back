<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class TestController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    #[Route('/api/test', name: 'test', methods: ['GET'])]
    #[IsGranted('PUBLIC_ACCESS')]
    public function test(): JsonResponse
    {
        return $this->json([
            'message' => 'API Backend fonctionne !',
            'timestamp' => date('Y-m-d H:i:s'),
            'status' => 'OK'
        ]);
    }

    #[Route('/api/test/professors', name: 'test_professors', methods: ['GET'])]
    #[IsGranted('PUBLIC_ACCESS')]
    public function testProfessors(): JsonResponse
    {
        try {
            // Récupérer les vrais professeurs de la BDD
            $professors = $this->userRepository->findByRole('ROLE_PROFESSOR');
            
            $professorsData = array_map(function($professor) {
                return [
                    'id' => $professor->getId(),
                    'firstName' => $professor->getName(),
                    'lastName' => $professor->getLastname(),
                    'email' => $professor->getEmail(),
                    'roles' => $professor->getRoles()
                ];
            }, $professors);

            return $this->json([
                'success' => true,
                'count' => count($professorsData),
                'professors' => $professorsData
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
                'professors' => []
            ], 500);
        }
    }

    #[Route('/api/test/students', name: 'test_students', methods: ['GET'])]
    #[IsGranted('PUBLIC_ACCESS')]
    public function testStudents(): JsonResponse
    {
        try {
            // Récupérer les vrais étudiants de la BDD
            $students = $this->userRepository->findByRole('ROLE_STUDENT');
            
            $studentsData = array_map(function($student) {
                return [
                    'id' => $student->getId(),
                    'firstName' => $student->getName(),
                    'lastName' => $student->getLastname(),
                    'email' => $student->getEmail(),
                    'roles' => $student->getRoles()
                ];
            }, $students);

            return $this->json([
                'success' => true,
                'count' => count($studentsData),
                'students' => $studentsData
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
                'students' => []
            ], 500);
        }
    }

    #[Route('/api/test/conversations', name: 'test_create_conversation', methods: ['POST'])]
    #[IsGranted('PUBLIC_ACCESS')]
    public function createConversation(): JsonResponse
    {
        try {
            // Simuler la création d'une conversation
            $conversation = [
                'id' => rand(1000, 9999),
                'title' => 'Marie Perez', // Nom de l'interlocuteur
                'participants' => [
                    [
                        'id' => 1,
                        'firstName' => 'Marie',
                        'lastName' => 'Perez',
                        'email' => 'marie.perez@univ.fr',
                        'roles' => ['ROLE_PROFESSOR']
                    ],
                    [
                        'id' => 2,
                        'firstName' => 'Angélique',
                        'lastName' => 'Mullet',
                        'email' => 'angelique.mullet@etudiant.univ.fr',
                        'roles' => ['ROLE_STUDENT']
                    ]
                ],
                'messages' => [], // Aucun message par défaut
                'createdAt' => '2025-09-06T14:30:00Z',
                'updatedAt' => '2025-09-06T14:37:00Z'
            ];

            return $this->json([
                'success' => true,
                'conversation' => $conversation
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/api/test/conversations/{id}/messages', name: 'test_send_message', methods: ['POST'])]
    #[IsGranted('PUBLIC_ACCESS')]
    public function sendMessage(int $id): JsonResponse
    {
        try {
            // Récupérer le contenu du message depuis la requête
            $input = json_decode(file_get_contents('php://input'), true);
            $content = $input['content'] ?? '';
            
            if (empty($content)) {
                return $this->json([
                    'success' => false,
                    'error' => 'Le contenu du message est requis'
                ], 400);
            }

            // Simuler l'envoi d'un message de l'utilisateur actuel
            $userMessage = [
                'id' => rand(10000, 99999),
                'content' => $content,
                'createdAt' => date('c'),
                'sender' => [
                    'id' => 43, // ID de l'utilisateur actuel (synchronisé avec le frontend)
                    'firstName' => 'Étudiant',
                    'lastName' => 'Actuel',
                    'name' => 'Étudiant', // Ajout pour compatibilité
                    'lastname' => 'Actuel', // Ajout pour compatibilité
                    'roles' => ['ROLE_STUDENT']
                ]
            ];

            // Simuler une réponse automatique du professeur après 2-5 secondes
            $professorReplies = [
                "Merci pour votre message ! Je vais vous répondre rapidement.",
                "Excellente question ! Laissez-moi vous expliquer...",
                "Je comprends votre préoccupation. Voici ce que je pense...",
                "C'est une très bonne observation. Continuez comme ça !",
                "Je suis là pour vous aider. N'hésitez pas à me poser d'autres questions.",
                "Parfait ! Vous progressez bien dans votre apprentissage.",
                "C'est exactement le type de réflexion que j'attends de mes étudiants.",
                "Je vais vous donner quelques conseils pour améliorer cela.",
                "Votre approche est très pertinente. Bravo !",
                "N'hésitez pas à approfondir ce sujet, c'est très intéressant."
            ];

            $professorReply = $professorReplies[array_rand($professorReplies)];
            
            $professorMessage = [
                'id' => rand(10000, 99999),
                'content' => $professorReply,
                'createdAt' => date('c', strtotime('+3 seconds')),
                'sender' => [
                    'id' => 2, // ID du professeur
                    'firstName' => 'Marie',
                    'lastName' => 'Perez',
                    'name' => 'Marie', // Ajout pour compatibilité
                    'lastname' => 'Perez', // Ajout pour compatibilité
                    'roles' => ['ROLE_PROFESSOR']
                ]
            ];

            return $this->json([
                'success' => true,
                'userMessage' => $userMessage,
                'professorMessage' => $professorMessage
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}