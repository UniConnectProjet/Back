<?php

namespace App\Tests\Controller;

use App\Tests\AbstractApiTestCase;
use App\Entity\User;
use App\Entity\Student;
use App\Entity\Classe;
use App\Entity\Category;
use App\Entity\Level;
use App\Entity\Grade;
use App\Entity\Course;
use App\Entity\CourseUnit;
use App\Entity\Semester;
use Doctrine\ORM\EntityManagerInterface;

class MeControllerTest extends AbstractApiTestCase
{
    private function createTestUser(EntityManagerInterface $em, string $email, array $roles): User
    {
        $user = new User();
        $user->setEmail($email . '+' . uniqid())
             ->setRoles($roles)
             ->setPassword(password_hash('password', PASSWORD_BCRYPT))
             ->setName('Test')
             ->setLastname('User')
             ->setBirthday(new \DateTime('1990-01-01'));
        
        $em->persist($user);
        return $user;
    }

    private function createTestStudent(EntityManagerInterface $em, User $user): Student
    {
        // Créer les entités nécessaires
        $category = new Category();
        $category->setName('Test Category');
        $em->persist($category);

        $level = new Level();
        $level->setName('L1');
        $em->persist($level);

        $classe = new Classe();
        $classe->setName('A1')
               ->setCategory($category)
               ->setLevelId($level);
        $em->persist($classe);

        $student = new Student();
        $student->setUser($user)
                ->setClasse($classe);
        $em->persist($student);

        return $student;
    }

    public function test_me_requires_auth(): void
    {
        $this->client->request('GET', '/api/me');
        $this->assertTrue(in_array($this->client->getResponse()->getStatusCode(), [401, 403]));
    }

    public function test_me_with_authenticated_user(): void
    {
        $user = $this->createTestUser($this->em, 'me@example.com', ['ROLE_USER']);
        $this->em->flush();
        
        $this->client->loginUser($user);
        $this->client->request('GET', '/api/me');
        
        $this->assertResponseIsSuccessful();
        $response = $this->decodeJson();
        
        $this->assertArrayHasKey('id', $response);
        $this->assertArrayHasKey('email', $response);
        $this->assertArrayHasKey('roles', $response);
        $this->assertArrayHasKey('name', $response);
        $this->assertArrayHasKey('lastname', $response);
        $this->assertArrayHasKey('displayName', $response);
        
        $this->assertEquals('me@example.com', $response['email']);
        $this->assertEquals(['ROLE_USER'], $response['roles']);
        $this->assertEquals('Test', $response['name']);
        $this->assertEquals('User', $response['lastname']);
    }

    public function test_me_grades_requires_auth(): void
    {
        $this->client->request('GET', '/api/me/grades');
        $this->assertTrue(in_array($this->client->getResponse()->getStatusCode(), [401, 403]));
    }

    public function test_me_grades_with_student_user(): void
    {
        $user = $this->createTestUser($this->em, 'student@example.com', ['ROLE_STUDENT']);
        $student = $this->createTestStudent($this->em, $user);
        $this->em->flush();
        
        $this->client->loginUser($user);
        $this->client->request('GET', '/api/me/grades');
        
        $this->assertResponseIsSuccessful();
        $response = $this->decodeJson();
        
        $this->assertArrayHasKey('student', $response);
        $this->assertArrayHasKey('grades', $response);
        $this->assertIsArray($response['grades']);
        
        $this->assertEquals('student@example.com', $response['student']['email']);
        $this->assertEquals('Test', $response['student']['name']);
        $this->assertEquals('User', $response['student']['lastname']);
    }

    public function test_me_grades_with_non_student_user(): void
    {
        $user = $this->createTestUser($this->em, 'prof@example.com', ['ROLE_PROFESSOR']);
        $this->em->flush();
        
        $this->client->loginUser($user);
        $this->client->request('GET', '/api/me/grades');
        
        $this->assertResponseStatusCodeSame(404);
        $response = $this->decodeJson();
        $this->assertArrayHasKey('message', $response);
        $this->assertStringContainsString('non trouvé comme étudiant', $response['message']);
    }

    public function test_me_grades_with_grades(): void
    {
        $user = $this->createTestUser($this->em, 'student@example.com', ['ROLE_STUDENT']);
        $student = $this->createTestStudent($this->em, $user);
        
        // Créer des données de test pour les notes
        $semester = new Semester();
        $semester->setName('S1')
                 ->setStartDate(new \DateTime('2024-09-01'))
                 ->setEndDate(new \DateTime('2025-01-15'));
        $this->em->persist($semester);

        $courseUnit = new CourseUnit();
        $courseUnit->setName('Test UE')
                   ->setSemester($semester)
                   ->setCategory($student->getClasse()->getCategory())
                   ->setLevels($student->getClasse()->getLevelId())
                   ->setAverage(10.0)
                   ->setAverageScore(10.0);
        $this->em->persist($courseUnit);

        $course = new Course();
        $course->setName('Test Course')
               ->setCourseUnit($courseUnit)
               ->setAverage(10.0);
        $this->em->persist($course);

        $grade = new Grade();
        $grade->setTitle('Test Grade')
              ->setGrade(15.0)
              ->setDividor(20.0)
              ->setStudent($student)
              ->setCourse($course);
        $this->em->persist($grade);

        $this->em->flush();
        
        $this->client->loginUser($user);
        $this->client->request('GET', '/api/me/grades');
        
        $this->assertResponseIsSuccessful();
        $response = $this->decodeJson();
        
        $this->assertArrayHasKey('grades', $response);
        $this->assertCount(1, $response['grades']);
        
        $gradeData = $response['grades'][0];
        $this->assertEquals('Test Grade', $gradeData['title']);
        $this->assertEquals(15.0, $gradeData['score']);
        $this->assertEquals(20.0, $gradeData['total']);
        $this->assertEquals('Test Course', $gradeData['courseName']);
    }
}
