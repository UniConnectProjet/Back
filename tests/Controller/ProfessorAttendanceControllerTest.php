<?php

namespace App\Tests\Controller;

use App\Entity\Absence;
use App\Entity\Professor;
use App\Entity\Student;
use App\Entity\User;
use App\Entity\Course;
use App\Entity\Classe;
use App\Entity\CourseSession;
use App\Entity\Semester;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProfessorAttendanceControllerTest extends WebTestCase
{
    private \Symfony\Bundle\FrameworkBundle\KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private static int $testCounter = 0;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()->get('doctrine')->getManager();
        $this->cleanDatabase();
    }

    protected function tearDown(): void
    {
        $this->cleanDatabase();
        parent::tearDown();
        $this->entityManager->close();
    }

    private function cleanDatabase(): void
    {
        $conn = $this->entityManager->getConnection();
        
        // Désactiver les contraintes de clé étrangère
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS=0');
        
        // Supprimer toutes les données dans l'ordre inverse des dépendances
        $conn->executeStatement('DELETE FROM absence');
        $conn->executeStatement('DELETE FROM course_session');
        $conn->executeStatement('DELETE FROM student');
        $conn->executeStatement('DELETE FROM professor');
        $conn->executeStatement('DELETE FROM course');
        $conn->executeStatement('DELETE FROM classe');
        $conn->executeStatement('DELETE FROM semester');
        $conn->executeStatement('DELETE FROM user');
        
        // Réactiver les contraintes de clé étrangère
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS=1');
    }

    private function createTestProfessor(): Professor
    {
        self::$testCounter++;
        $user = new User();
        $user->setEmail("professor" . self::$testCounter . "@test.com");
        $user->setPassword('password');
        $user->setName('John');
        $user->setLastname('Doe');
        $user->setBirthday(new \DateTime('1990-01-01'));
        $user->setRoles(['ROLE_PROFESSOR']);

        $professor = new Professor();
        $professor->setUserId($user);
        $user->setProfessor($professor);

        $this->entityManager->persist($user);
        $this->entityManager->persist($professor);
        $this->entityManager->flush();

        return $professor;
    }

    private function createTestData(Professor $professor): array
    {
        // Créer un semestre
        $semester = new Semester();
        $semester->setName('S1 2024');
        $semester->setStartDate(new \DateTime('2024-09-01'));
        $semester->setEndDate(new \DateTime('2024-12-31'));
        $this->entityManager->persist($semester);

        // Créer une classe
        $classe = new Classe();
        $classe->setName('Test Class');
        $this->entityManager->persist($classe);

        // Créer un cours
        $course = new Course();
        $course->setName('Test Course');
        $course->addProfessor($professor);
        $this->entityManager->persist($course);

        // Créer une session de cours
        $courseSession = new CourseSession();
        $courseSession->setCourse($course);
        $courseSession->setStartTime(new \DateTime('09:00:00'));
        $courseSession->setEndTime(new \DateTime('11:00:00'));
        $courseSession->setDate(new \DateTime('2024-10-15'));
        $this->entityManager->persist($courseSession);

        // Créer un étudiant
        $user = new User();
        $user->setEmail("student" . self::$testCounter . "@test.com");
        $user->setPassword('password');
        $user->setName('Jane');
        $user->setLastname('Smith');
        $user->setBirthday(new \DateTime('2000-01-01'));
        $user->setRoles(['ROLE_STUDENT']);

        $student = new Student();
        $student->setUser($user);
        $student->setClasse($classe);
        $user->setStudent($student);

        $this->entityManager->persist($user);
        $this->entityManager->persist($student);

        // Créer des absences de test
        $absence1 = new Absence();
        $absence1->setStudent($student);
        $absence1->setCourseSession($courseSession);
        $absence1->setSemester($semester);
        $absence1->setStartedDate(new \DateTime('2024-10-15 09:00:00'));
        $absence1->setEndedDate(new \DateTime('2024-10-15 11:00:00'));
        $absence1->setJustified(false);
        $absence1->setPresenceStatus(Absence::STATUS_ABSENT);
        $this->entityManager->persist($absence1);

        $absence2 = new Absence();
        $absence2->setStudent($student);
        $absence2->setCourseSession($courseSession);
        $absence2->setSemester($semester);
        $absence2->setStartedDate(new \DateTime('2024-10-16 09:00:00'));
        $absence2->setEndedDate(new \DateTime('2024-10-16 11:00:00'));
        $absence2->setJustified(true);
        $absence2->setPresenceStatus(Absence::STATUS_ABSENT);
        $this->entityManager->persist($absence2);

        $this->entityManager->flush();

        return [
            'professor' => $professor,
            'student' => $student,
            'course' => $course,
            'classe' => $classe,
            'courseSession' => $courseSession,
            'semester' => $semester
        ];
    }

    public function testGetAttendanceStats(): void
    {
        $professor = $this->createTestProfessor();
        $this->createTestData($professor);

        // Se connecter en tant que professeur
        $this->client->loginUser($professor->getUserId());

        $this->client->request('GET', '/api/prof/attendance/stats', [
            'startDate' => '2024-10-01',
            'endDate' => '2024-10-31',
            'groupBy' => 'week'
        ]);

        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('period', $response);
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('summary', $response);
        $this->assertEquals('week', $response['period']['groupBy']);
    }

    public function testGetAttendanceByClass(): void
    {
        $professor = $this->createTestProfessor();
        $this->createTestData($professor);

        $this->client->loginUser($professor->getUserId());

        $this->client->request('GET', '/api/prof/attendance/by-class', [
            'startDate' => '2024-10-01',
            'endDate' => '2024-10-31'
        ]);

        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('period', $response);
        $this->assertArrayHasKey('classes', $response);
        $this->assertIsArray($response['classes']);
    }

    public function testGetTopAbsentees(): void
    {
        $professor = $this->createTestProfessor();
        $this->createTestData($professor);

        $this->client->loginUser($professor->getUserId());

        $this->client->request('GET', '/api/prof/attendance/top-absentees', [
            'startDate' => '2024-10-01',
            'endDate' => '2024-10-31',
            'limit' => 5
        ]);

        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('period', $response);
        $this->assertArrayHasKey('topAbsentees', $response);
        $this->assertIsArray($response['topAbsentees']);
    }

    public function testGetAttendanceDetail(): void
    {
        $professor = $this->createTestProfessor();
        $this->createTestData($professor);

        $this->client->loginUser($professor->getUserId());

        $this->client->request('GET', '/api/prof/attendance/detail', [
            'startDate' => '2024-10-01',
            'endDate' => '2024-10-31'
        ]);

        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('period', $response);
        $this->assertArrayHasKey('details', $response);
        $this->assertIsArray($response['details']);
    }

    public function testGetAttendanceStatsWithInvalidGroupBy(): void
    {
        $professor = $this->createTestProfessor();
        $this->client->loginUser($professor->getUserId());

        $this->client->request('GET', '/api/prof/attendance/stats', [
            'startDate' => '2024-10-01',
            'endDate' => '2024-10-31',
            'groupBy' => 'invalid'
        ]);

        $this->assertResponseStatusCodeSame(400);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $response);
    }

    public function testGetAttendanceStatsWithoutDates(): void
    {
        $professor = $this->createTestProfessor();
        $this->client->loginUser($professor->getUserId());

        $this->client->request('GET', '/api/prof/attendance/stats');

        $this->assertResponseStatusCodeSame(400);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $response);
    }
}
