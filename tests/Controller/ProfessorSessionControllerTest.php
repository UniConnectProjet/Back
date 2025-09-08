<?php

namespace App\Tests\Controller;

use App\Tests\AbstractApiTestCase;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use App\Entity\Professor;
use App\Entity\Category;
use App\Entity\Level;
use App\Entity\Classe;
use App\Entity\Semester;
use App\Entity\CourseUnit;
use App\Entity\Course;
use App\Entity\Student;
use App\Entity\CourseSession;

final class ProfessorSessionControllerTest extends AbstractApiTestCase
{
    /** Petit helper pour générer des emails uniques par test */
    private function uniq(string $prefix): string
    {
        return sprintf('%s+%s@example.com', $prefix, bin2hex(random_bytes(4)));
    }

    /**
     * Seed minimal et cohérent pour nos 4 tests.
     * Retourne toutes les entités utiles.
     */
    private function seed(EntityManagerInterface $em): array
    {
        // --- Users
        $admin = (new User())
            ->setEmail($this->uniq('admin'))
            ->setRoles(['ROLE_ADMIN'])
            ->setPassword(password_hash('admin', PASSWORD_BCRYPT))
            ->setName('Admin')->setLastname('User')
            ->setBirthday(new \DateTime('2000-01-01'));
        $em->persist($admin);

        $profUser = (new User())
            ->setEmail($this->uniq('prof'))
            ->setRoles(['ROLE_PROFESSOR'])
            ->setPassword(password_hash('prof', PASSWORD_BCRYPT))
            ->setName('Prof')->setLastname('User')
            ->setBirthday(new \DateTime('1980-01-01'));
        $em->persist($profUser);

        $otherProfUser = (new User())
            ->setEmail($this->uniq('prof2'))
            ->setRoles(['ROLE_PROFESSOR'])
            ->setPassword(password_hash('prof2', PASSWORD_BCRYPT))
            ->setName('Prof2')->setLastname('User')
            ->setBirthday(new \DateTime('1978-01-01'));
        $em->persist($otherProfUser);

        // --- Professors liés aux users (ton setter s’appelle setUserId)
        $prof = (new Professor())->setUserId($profUser);
        $em->persist($prof);
        $otherProf = (new Professor())->setUserId($otherProfUser);
        $em->persist($otherProf);

        // --- Taxonomie
        $category = (new Category())->setName('TestCat');
        $em->persist($category);
        $level = (new Level())->setName('L1');
        $em->persist($level);

        // --- Classe
        $classe = (new Classe())
            ->setName('A1')
            ->setCategory($category)
            ->setLevelId($level);
        $em->persist($classe);

        // --- Semestre
        $semester = (new Semester())
            ->setName('S1')
            ->setStartDate(new \DateTime('2024-09-01'))
            ->setEndDate(new \DateTime('2025-01-15'));
        $em->persist($semester);

        // --- UE (CourseUnit)
        $cu = (new CourseUnit())
            ->setName('UE Test')
            ->setSemester($semester)
            ->setCategory($category)
            // signature de ton entité : setLevels(?Level $levels)
            ->setLevels($level)
            ->setAverage(10.0)
            ->setAverageScore(10.0);
        $em->persist($cu);

        // --- Cours
        $course = (new Course())
            ->setName('Cours Test')
            ->setCourseUnit($cu)
            ->setAverage(10.0);
        $em->persist($course);

        // --- Étudiants (3 suffisent pour les assertions)
        $students = [];
        for ($i = 0; $i < 3; $i++) {
            $su = (new User())
                ->setEmail($this->uniq('s'.$i))
                ->setRoles(['ROLE_STUDENT'])
                ->setPassword(password_hash('pwd', PASSWORD_BCRYPT))
                ->setName('S'.$i)->setLastname('User')
                ->setBirthday(new \DateTime('2002-01-01'));
            $em->persist($su);

            $st = (new Student())
                ->setUser($su)
                ->setClasse($classe);
            $em->persist($st);
            $students[] = $st;
        }

        // --- Séance
        $session = (new CourseSession())
            ->setCourse($course)
            ->setClasse($classe)
            ->setProfessor($prof)
            ->setRoom('B204')
            ->setStartAt(new \DateTimeImmutable('+1 day 08:00'))
            ->setEndAt(new \DateTimeImmutable('+1 day 10:00'));
        $em->persist($session);

        $em->flush();

        return compact(
            'profUser',
            'otherProfUser',
            'prof',
            'otherProf',
            'classe',
            'course',
            'students',
            'session'
        );
    }

    public function testRosterReturnsStudentsOfClass(): void
    {
        $data = $this->seed($this->em);

        // authentification du prof
        $this->client->loginUser($data['profUser']);

        $this->client->request('GET', '/api/prof/sessions/'.$data['session']->getId().'/roster');
        $this->assertResponseIsSuccessful();

        $json = $this->decodeJson();
        $this->assertSame($data['classe']->getId(), $json['classeId']);
        $this->assertCount(count($data['students']), $json['students']);
    }

    public function testListSessionsWithStudentCount(): void
    {
        $data = $this->seed($this->em);

        $this->client->loginUser($data['profUser']);

        $this->client->request('GET', '/api/prof/sessions/with-students');
        $this->assertResponseIsSuccessful();

        $arr = $this->decodeJson();
        $this->assertNotEmpty($arr);

        $found = array_filter($arr, fn($r) => $r['sessionId'] === $data['session']->getId());
        $this->assertNotEmpty($found);

        $row = array_values($found)[0];
        $this->assertSame(count($data['students']), $row['studentCount']);
    }

}