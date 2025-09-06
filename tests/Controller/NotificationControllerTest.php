<?php

namespace App\Tests\Controller;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class NotificationControllerTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()->get(EntityManagerInterface::class);
    }

    public function testGetNotifications(): void
    {
        $user = $this->createTestUser('user@test.com', 'User', 'Test');
        $this->createTestNotification($user, 'Test Notification', 'This is a test notification');
        $this->entityManager->flush();

        $this->client->loginUser($user);

        $this->client->request('GET', '/api/notifications');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('notifications', $response);
        $this->assertArrayHasKey('unreadCount', $response);
        $this->assertArrayHasKey('pagination', $response);
    }

    public function testGetUnreadNotifications(): void
    {
        $user = $this->createTestUser('user@test.com', 'User', 'Test');
        $this->createTestNotification($user, 'Unread Notification', 'This is unread', false);
        $this->createTestNotification($user, 'Read Notification', 'This is read', true);
        $this->entityManager->flush();

        $this->client->loginUser($user);

        $this->client->request('GET', '/api/notifications/unread');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('notifications', $response);
        $this->assertArrayHasKey('count', $response);
        $this->assertEquals(1, $response['count']);
    }

    public function testMarkNotificationAsRead(): void
    {
        $user = $this->createTestUser('user@test.com', 'User', 'Test');
        $notification = $this->createTestNotification($user, 'Test Notification', 'This is a test notification', false);
        $this->entityManager->flush();

        $this->client->loginUser($user);

        $this->client->request('PUT', "/api/notifications/{$notification->getId()}/read");

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($response['success']);

        // Vérifier que la notification est marquée comme lue
        $this->entityManager->refresh($notification);
        $this->assertTrue($notification->isRead());
    }

    public function testMarkAllNotificationsAsRead(): void
    {
        $user = $this->createTestUser('user@test.com', 'User', 'Test');
        $this->createTestNotification($user, 'Notification 1', 'This is notification 1', false);
        $this->createTestNotification($user, 'Notification 2', 'This is notification 2', false);
        $this->entityManager->flush();

        $this->client->loginUser($user);

        $this->client->request('PUT', '/api/notifications/read-all');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($response['success']);
        $this->assertEquals(2, $response['count']);
    }

    public function testGetNotificationCount(): void
    {
        $user = $this->createTestUser('user@test.com', 'User', 'Test');
        $this->createTestNotification($user, 'Unread 1', 'This is unread 1', false);
        $this->createTestNotification($user, 'Unread 2', 'This is unread 2', false);
        $this->createTestNotification($user, 'Read 1', 'This is read 1', true);
        $this->entityManager->flush();

        $this->client->loginUser($user);

        $this->client->request('GET', '/api/notifications/count');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('count', $response);
        $this->assertEquals(2, $response['count']);
    }

    public function testUnauthorizedAccess(): void
    {
        $user1 = $this->createTestUser('user1@test.com', 'User1', 'Test1');
        $user2 = $this->createTestUser('user2@test.com', 'User2', 'Test2');
        $notification = $this->createTestNotification($user1, 'Test Notification', 'This is a test notification');
        $this->entityManager->flush();

        // user2 essaie d'accéder à la notification de user1
        $this->client->loginUser($user2);

        $this->client->request('PUT', "/api/notifications/{$notification->getId()}/read");

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

    private function createTestNotification(User $user, string $title, string $content, bool $isRead = false): Notification
    {
        $notification = new Notification();
        $notification->setTitle($title);
        $notification->setContent($content);
        $notification->setType('test');
        $notification->setUser($user);
        $notification->setIsRead($isRead);

        $this->entityManager->persist($notification);

        return $notification;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }
}
