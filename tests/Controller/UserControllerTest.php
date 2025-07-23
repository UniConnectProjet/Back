<?php

namespace App\Tests\Controller;

use App\Tests\DataFixtures\TestUserFixtures;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserControllerTest extends WebTestCase
{
    private \Symfony\Bundle\FrameworkBundle\KernelBrowser $client;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $container = $this->client->getContainer();

        $em = $container->get(EntityManagerInterface::class);

        // Purge de la base pour éviter les duplications
        $purger = new ORMPurger($em);
        $purger->purge();

        // Rechargement des fixtures
        $hasher = $container->get(UserPasswordHasherInterface::class);
        $fixture = new TestUserFixtures($hasher);
        $fixture->load($em);
    }

    private function authenticate(): void
    {
        $this->client->request('POST', '/api/login_check', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'username' => 'test@example.com',
            'password' => 'test',
        ]));

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        if (!isset($data['token'])) {
            throw new \RuntimeException('Token JWT manquant dans la réponse.');
        }

        $this->client->setServerParameter('HTTP_Authorization', sprintf('Bearer %s', $data['token']));
    }

    public function testIndexRoute(): void
    {
        $this->client->request('GET', '/user');
        $this->assertResponseIsSuccessful();
        $this->assertJson($this->client->getResponse()->getContent());
    }

    public function testGetAllStudents(): void
    {
        $this->authenticate();
        $this->client->request('GET', '/api/students');
        $this->assertResponseIsSuccessful();
    }

    public function testCreateUser(): void
    {
        $this->authenticate();
        $this->client->request('POST', '/api/user', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'Test',
            'lastname' => 'User',
            'email' => 'testuser@example.com',
            'password' => 'password123',
            'birthday' => '2000-01-01',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertJson($this->client->getResponse()->getContent());
    }

    public function testCreateUserWithInvalidData(): void
    {
        $this->authenticate();
        $this->client->request('POST', '/api/user', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], '{}');

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testDeleteUserNotFound(): void
    {
        $this->authenticate();
        $this->client->request('DELETE', '/api/users/999999');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}