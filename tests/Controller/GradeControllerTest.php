<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use App\Tests\DataFixtures\TestFixtures;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class GradeControllerTest extends WebTestCase
{
    private \Symfony\Bundle\FrameworkBundle\KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();

        $container = $this->client->getContainer();
        $this->em = $container->get(EntityManagerInterface::class);

        // Purge & Load fixtures
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

    public function testGetAllGrades(): void
    {
        $this->authenticate();

        $this->client->request('GET', '/api/grades/');
        $this->assertResponseIsSuccessful();
        $this->assertJson($this->client->getResponse()->getContent());
    }

    public function testGetGradesByValidStudentId(): void
    {
        $this->authenticate();

        $student = $this->em->getRepository(\App\Entity\Student::class)->findOneBy([]);
        $this->assertNotNull($student);
        $this->client->request('GET', '/api/grades/student/' . $student->getId());

        $this->assertResponseIsSuccessful();
        $this->assertJson($this->client->getResponse()->getContent());
    }

    public function testGetGradesByInvalidStudentId(): void
    {
        $this->authenticate();
        $this->client->request('GET', '/api/grades/student/99999');

        $this->assertResponseIsSuccessful(); // même avec un ID inexistant, ça retourne une liste vide
        $this->assertJson($this->client->getResponse()->getContent());
    }

    public function testGetGradesBySemester(): void
    {
        $this->authenticate();

        $semester = $this->em->getRepository(\App\Entity\Semester::class)->findOneBy([]);
        $this->assertNotNull($semester);
        $this->client->request('GET', '/api/grades/semester/' . $semester->getId());

        $this->assertResponseIsSuccessful();
        $this->assertJson($this->client->getResponse()->getContent());
    }

    public function testDeleteGrade(): void
    {
        $this->authenticate();

        $grade = $this->em->getRepository(\App\Entity\Grade::class)->findOneBy([]);
        if (!$grade) {
            $this->markTestSkipped('No grade available to delete.');
        }

        $this->client->request('DELETE', '/api/grades/' . $grade->getId());

        $this->assertResponseIsSuccessful();
        $this->assertEquals('"Grade deleted"', $this->client->getResponse()->getContent());
    }

    public function testAddGradeForStudent(): void
    {
        $this->authenticate();

        $student = $this->em->getRepository(\App\Entity\Student::class)->findOneBy([]);
        $this->assertNotNull($student, 'Aucun étudiant trouvé pour tester.');

        $payload = json_encode([
            'grade' => 16.5,
            'dividor' => 20,
            'title' => 'Test Note'
        ]);

        $this->client->request(
            'POST',
            '/api/grades/student/' . $student->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $payload
        );

        $this->assertResponseStatusCodeSame(201);
        $this->assertStringContainsString('Grade added', $this->client->getResponse()->getContent());
    }


    public function testUpdateGrade(): void
    {
        $this->authenticate();

        $student = $this->em->getRepository(\App\Entity\Student::class)->findOneBy([]);
        $this->assertNotNull($student, 'Aucun étudiant trouvé.');

        $grade = new \App\Entity\Grade();
        $grade->setStudent($student);
        $grade->setGrade(10);
        $grade->setDividor(20);
        $grade->setTitle('Initial Test');
        $this->em->persist($grade);
        $this->em->flush();

        $updatedData = json_encode([
            'grade' => 18,
            'dividor' => 20,
            'title' => 'Updated Test'
        ]);

        $this->client->request(
            'PUT',
            '/api/grades/' . $student->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $updatedData
        );

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Grade updated', $this->client->getResponse()->getContent());
    }


}
