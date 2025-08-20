<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Symfony\Component\HttpFoundation\Response;
use App\Tests\DataFixtures\TestFixtures;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SemesterControllerTest extends WebTestCase
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

    public function testGetAllSemesters(): void
    {
        $this->authenticate();

        $this->client->request('GET', '/api/semesters');

        $this->assertResponseIsSuccessful();
        $this->assertJson($this->client->getResponse()->getContent());
    }

    public function testGetSemestersByValidStudentId(): void
    {
        $this->authenticate();

        // On récupère le Student via le repository
        $student = $this->em->getRepository(\App\Entity\Student::class)->findOneBy([]);

        $this->assertNotNull($student, 'Le student n\'a pas été trouvé en base.');

        $studentId = $student->getId();

        $this->client->request('GET', '/api/semester/' . $studentId);

        $this->assertResponseIsSuccessful();
        $this->assertJson($this->client->getResponse()->getContent());
    }


    public function testGetSemestersByInvalidStudentId(): void
    {
        $this->authenticate();

        $this->client->request('GET', '/api/semester/9999');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $this->assertJsonStringEqualsJsonString(
            json_encode(['error' => 'Student not found']),
            $this->client->getResponse()->getContent()
        );
    }
}