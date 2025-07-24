<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Tests\DataFixtures\TestFixtures;
use Symfony\Component\HttpFoundation\Response;

class StudentControllerTest extends WebTestCase
{
    private \Symfony\Bundle\FrameworkBundle\KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();

        $container = $this->client->getContainer();
        $this->em = $container->get(EntityManagerInterface::class);

        $purger = new ORMPurger($this->em);
        $purger->purge();

        $hasher = $container->get(UserPasswordHasherInterface::class);
        $fixtures = new TestFixtures($hasher);
        $fixtures->load($this->em);
    }

    private function authenticate(): void
    {
        $this->client->request('POST', '/api/login_check', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'username' => 'test@example.com',
            'password' => 'test',
        ]));

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $data);

        $this->client->setServerParameter('HTTP_Authorization', 'Bearer ' . $data['token']);
    }

    public function testGetAllStudents(): void
    {
        $this->authenticate();
        $this->client->request('GET', '/api/students');

        $this->assertResponseIsSuccessful();
        $this->assertJson($this->client->getResponse()->getContent());
    }

    public function testGetNonexistentStudent(): void
    {
        $this->authenticate();
        $this->client->request('GET', '/api/students/9999');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testCreateStudentWithMissingFields(): void
    {
        $this->authenticate();
        $this->client->request('POST', '/api/student', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([]));

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testCreateAndDeleteStudent(): void
    {
        $this->authenticate();

        // Récupération des entités créées par les fixtures
        $user = $this->em->getRepository(\App\Entity\User::class)->findOneBy(['email' => 'test@example.com']);
        $classe = $this->em->getRepository(\App\Entity\Classe::class)->findOneBy(['name' => 'I1']);
        $semester = $this->em->getRepository(\App\Entity\Semester::class)->findOneBy(['name' => 'S1']);

        $this->assertNotNull($user, 'User should exist');
        $this->assertNotNull($classe, 'Classe should exist');
        $this->assertNotNull($semester, 'Semester should exist');

        $payload = [
            'user' => ['id' => $user->getId()],
            'classe' => ['id' => $classe->getId()],
            'semesters' => [['id' => $semester->getId()]]
        ];

        $this->client->request('POST', '/api/student', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode($payload));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $studentId = $responseData['id'] ?? null;
        $this->assertNotNull($studentId);

        $this->client->request('DELETE', '/api/students/' . $studentId);
        $this->assertResponseIsSuccessful();
    }


    public function testUpdateStudentNotFound(): void
    {
        $this->authenticate();

        $this->client->request('PUT', '/api/students/9999', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'user' => ['id' => 1],
            'classe' => ['id' => 1],
            'semesters' => [['id' => 1]]
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}