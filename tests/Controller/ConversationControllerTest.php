<?php

namespace App\Tests\Controller;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\User;
use App\Repository\ConversationRepository;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ConversationControllerTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    private ConversationRepository $conversationRepository;
    private MessageRepository $messageRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()->get(EntityManagerInterface::class);
        $this->userRepository = $this->entityManager->getRepository(User::class);
        $this->conversationRepository = $this->entityManager->getRepository(Conversation::class);
        $this->messageRepository = $this->entityManager->getRepository(Message::class);
    }

    public function testCreateConversation(): void
    {
        // Créer des utilisateurs de test
        $user1 = $this->createTestUser('user1@test.com', 'User1', 'Test1');
        $user2 = $this->createTestUser('user2@test.com', 'User2', 'Test2');

        $this->entityManager->flush();

        // Se connecter en tant que user1
        $this->client->loginUser($user1);

        $conversationData = [
            'participantIds' => [$user2->getId()],
            'title' => 'Test Conversation'
        ];

        $this->client->request('POST', '/api/conversations', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode($conversationData));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $response);
        $this->assertEquals('Test Conversation', $response['title']);
    }

    public function testGetConversations(): void
    {
        $user = $this->createTestUser('user@test.com', 'User', 'Test');
        $this->entityManager->flush();

        $this->client->loginUser($user);

        $this->client->request('GET', '/api/conversations');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($response);
    }

    public function testSendMessage(): void
    {
        // Créer une conversation avec deux utilisateurs
        $user1 = $this->createTestUser('user1@test.com', 'User1', 'Test1');
        $user2 = $this->createTestUser('user2@test.com', 'User2', 'Test2');
        
        $conversation = new Conversation();
        $conversation->addParticipant($user1);
        $conversation->addParticipant($user2);
        $conversation->setTitle('Test Conversation');
        
        $this->entityManager->persist($conversation);
        $this->entityManager->flush();

        $this->client->loginUser($user1);

        $messageData = [
            'content' => 'Hello, this is a test message!'
        ];

        $this->client->request('POST', "/api/conversations/{$conversation->getId()}/messages", [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode($messageData));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $response);
        $this->assertEquals('Hello, this is a test message!', $response['content']);
    }

    public function testGetMessages(): void
    {
        $user1 = $this->createTestUser('user1@test.com', 'User1', 'Test1');
        $user2 = $this->createTestUser('user2@test.com', 'User2', 'Test2');
        
        $conversation = new Conversation();
        $conversation->addParticipant($user1);
        $conversation->addParticipant($user2);
        
        $this->entityManager->persist($conversation);
        $this->entityManager->flush();

        $this->client->loginUser($user1);

        $this->client->request('GET', "/api/conversations/{$conversation->getId()}/messages");

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('messages', $response);
        $this->assertArrayHasKey('pagination', $response);
    }

    public function testUnauthorizedAccess(): void
    {
        $user1 = $this->createTestUser('user1@test.com', 'User1', 'Test1');
        $user2 = $this->createTestUser('user2@test.com', 'User2', 'Test2');
        $user3 = $this->createTestUser('user3@test.com', 'User3', 'Test3');
        
        $conversation = new Conversation();
        $conversation->addParticipant($user1);
        $conversation->addParticipant($user2);
        
        $this->entityManager->persist($conversation);
        $this->entityManager->flush();

        // user3 essaie d'accéder à une conversation dont il ne fait pas partie
        $this->client->loginUser($user3);

        $this->client->request('GET', "/api/conversations/{$conversation->getId()}");

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    private function createTestUser(string $email, string $name, string $lastname): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setName($name);
        $user->setLastname($lastname);
        $user->setPassword('password');
        $user->setRoles(['ROLE_USER']);
        $user->setBirthday(new \DateTime('1990-01-01'));

        $this->entityManager->persist($user);

        return $user;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }
}
