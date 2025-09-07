<?php

namespace App\Tests\Controller;

use App\Entity\Grade;
use App\Entity\Professor;
use App\Entity\Course;
use App\Entity\Classe;
use App\Entity\Student;
use App\Entity\User;
use App\Repository\GradeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ProfessorGradeControllerTest extends WebTestCase
{
    private \Symfony\Bundle\FrameworkBundle\KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private GradeRepository $gradeRepository;
    private static int $testCounter = 0;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()->get('doctrine')->getManager();
        $this->gradeRepository = $this->entityManager->getRepository(Grade::class);
        
        // Nettoyer la base de données avant chaque test
        $this->cleanDatabase();
    }

    public function testGetGradesOverview(): void
    {
        // Créer un professeur de test
        $professor = $this->createTestProfessor();
        
        // Créer des données de test
        $this->createTestData($professor);

        // Se connecter en tant que professeur
        $this->client->loginUser($professor->getUserId());

        // Faire la requête
        $this->client->request('GET', '/api/prof/grades/overview');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('professor', $responseData);
        $this->assertArrayHasKey('range', $responseData);
        $this->assertArrayHasKey('byCourse', $responseData);
        $this->assertArrayHasKey('courseAverageGlobal', $responseData);
    }

    public function testGetCourseClassGrades(): void
    {
        // Créer un professeur de test
        $professor = $this->createTestProfessor();
        
        // Créer des données de test
        $course = $this->createTestCourse($professor);
        $class = $this->createTestClass();
        $course->addClass($class);
        $this->entityManager->flush();

        // Se connecter en tant que professeur
        $this->client->loginUser($professor->getUserId());

        // Faire la requête
        $this->client->request('GET', "/api/prof/courses/{$course->getId()}/classes/{$class->getId()}/grades");

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('course', $responseData);
        $this->assertArrayHasKey('class', $responseData);
        $this->assertArrayHasKey('controls', $responseData);
        $this->assertArrayHasKey('classAverage', $responseData);
    }

    public function testGetGradesHistory(): void
    {
        // Créer un professeur de test
        $professor = $this->createTestProfessor();
        
        // Créer des données de test
        $this->createTestData($professor);

        // Se connecter en tant que professeur
        $this->client->loginUser($professor->getUserId());

        // Faire la requête
        $this->client->request('GET', '/api/prof/grades/history');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertIsArray($responseData);
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
        
        // Définir la relation bidirectionnelle
        $user->setProfessor($professor);

        $this->entityManager->persist($user);
        $this->entityManager->persist($professor);
        $this->entityManager->flush();

        return $professor;
    }

    private function createTestCourse(Professor $professor): Course
    {
        $course = new Course();
        $course->setName('Test Course');
        $course->setAverage(0);
        $course->addProfessor($professor);

        $this->entityManager->persist($course);
        $this->entityManager->flush();

        return $course;
    }

    private function createTestClass(): Classe
    {
        $class = new Classe();
        $class->setName('Test Class');

        $this->entityManager->persist($class);
        $this->entityManager->flush();

        return $class;
    }

    private function createTestData(Professor $professor): void
    {
        $course = $this->createTestCourse($professor);
        $class = $this->createTestClass();
        $course->addClass($class);

        // Créer un étudiant avec un email unique
        $user = new User();
        $user->setEmail("student" . self::$testCounter . "@test.com");
        $user->setPassword('password');
        $user->setName('Jane');
        $user->setLastname('Smith');
        $user->setBirthday(new \DateTime('2000-01-01'));
        $user->setRoles(['ROLE_STUDENT']);

        $student = new Student();
        $student->setUser($user);
        $student->setClasse($class);

        $this->entityManager->persist($user);
        $this->entityManager->persist($student);

        // Créer des notes de test
        $grade1 = new Grade();
        $grade1->setGrade(15.0);
        $grade1->setDividor(20.0);
        $grade1->setTitle('DS 1');
        $grade1->setStudent($student);
        $grade1->setCourse($course);
        $grade1->setCreatedAt(new \DateTimeImmutable());

        $grade2 = new Grade();
        $grade2->setGrade(12.0);
        $grade2->setDividor(20.0);
        $grade2->setTitle('DS 1');
        $grade2->setStudent($student);
        $grade2->setCourse($course);
        $grade2->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($grade1);
        $this->entityManager->persist($grade2);
        $this->entityManager->flush();
    }

    private function cleanDatabase(): void
    {
        $conn = $this->entityManager->getConnection();
        
        // Désactiver les contraintes de clé étrangère
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS=0');
        
        // Supprimer toutes les données dans l'ordre inverse des dépendances
        $conn->executeStatement('DELETE FROM grade');
        $conn->executeStatement('DELETE FROM student');
        $conn->executeStatement('DELETE FROM professor');
        $conn->executeStatement('DELETE FROM course');
        $conn->executeStatement('DELETE FROM classe');
        $conn->executeStatement('DELETE FROM user');
        
        // Réactiver les contraintes de clé étrangère
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS=1');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }
}
