<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

use App\Entity\User;
use App\Entity\Category;
use App\Entity\Level;
use App\Entity\Classe;
use App\Entity\Semester;
use App\Entity\CourseUnit;
use App\Entity\Course;
use App\Entity\Student;
use App\Entity\Grade;
use App\Entity\Absence;
use App\Entity\CourseSession;
use App\Entity\Professor;

// Lancer les fixtures avec `php bin/console doctrine:fixtures:load --env=test --group=test --no-interaction`
final class TestFixtures extends Fixture implements FixtureGroupInterface
{
    public function __construct(private UserPasswordHasherInterface $hasher) {}

    public static function getGroups(): array
    {
        return ['test'];
    }

    public function load(ObjectManager $em): void
    {
        // ========= Users =========
        $admin = new User();
        $admin->setEmail('admin@example.com');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->hasher->hashPassword($admin, 'admin'));
        $admin->setName('Admin');
        $admin->setLastname('User');
        $admin->setBirthday(new \DateTime('2000-01-01'));
        $em->persist($admin);

        // User "prof"
        $profUser = new User();
        $profUser->setEmail('prof@example.com');
        $profUser->setRoles(['ROLE_PROFESSOR']);
        $profUser->setPassword($this->hasher->hashPassword($profUser, 'prof'));
        $profUser->setName('Prof');
        $profUser->setLastname('User');
        $profUser->setBirthday(new \DateTime('1980-01-01'));
        $em->persist($profUser);

        // Professor lié au User
        $professor = new Professor();
        $professor->setUserId($profUser);
        if (method_exists($professor, 'setIsActive')) {
            $professor->setIsActive(true);
        }

        if (method_exists($professor, 'setWeeklyAvailability')) {
            $professor->setWeeklyAvailability([
                'MON' => [['08:00','12:00']],
                'TUE' => [['10:00','12:00'], ['14:00','16:00']],
                'WED' => [['09:00','11:00']],
                'THU' => [['13:00','16:00']],
                'FRI' => [['14:00','17:00']],
                'SAT' => [], 'SUN' => [],
            ]);
        }
        $em->persist($professor);

        // User "test" (étudiant)
        $test = new User();
        $test->setEmail('test@example.com');
        $test->setRoles(['ROLE_STUDENT']);
        $test->setName('Test');
        $test->setLastname('Student');
        $test->setPassword($this->hasher->hashPassword($test, 'test'));
        $test->setBirthday(new \DateTime('2002-01-01'));
        $em->persist($test);

        // User "student2" (autre étudiant)
        $student2 = new User();
        $student2->setEmail('student2@example.com');
        $student2->setRoles(['ROLE_STUDENT']);
        $student2->setName('Student');
        $student2->setLastname('Two');
        $student2->setPassword($this->hasher->hashPassword($student2, 'student2'));
        $student2->setBirthday(new \DateTime('2002-05-15'));
        $em->persist($student2);

        // User "prof2" (autre professeur)
        $prof2User = new User();
        $prof2User->setEmail('prof2@example.com');
        $prof2User->setRoles(['ROLE_PROFESSOR']);
        $prof2User->setPassword($this->hasher->hashPassword($prof2User, 'prof2'));
        $prof2User->setName('Prof');
        $prof2User->setLastname('Two');
        $prof2User->setBirthday(new \DateTime('1975-03-20'));
        $em->persist($prof2User);

        // Professor 2 lié au User
        $professor2 = new Professor();
        $professor2->setUserId($prof2User);
        if (method_exists($professor2, 'setIsActive')) {
            $professor2->setIsActive(true);
        }
        $em->persist($professor2);

        // ========= Taxonomie =========
        $category = new Category();
        $category->setName('Informatique');
        $em->persist($category);

        $level = new Level();
        $level->setName('L1');
        $em->persist($level);

        // flush anticipé si jamais certains setters attendent des IDs
        $em->flush();

        $classe = new Classe();
        $classe->setName('A1');
        // D'après ton entité, le setter est bien setLevelId(?Level)
        $classe->setLevelId($level);
        $classe->setCategory($category);
        $em->persist($classe);

        // ========= Semestre =========
        $semester = new Semester();
        $semester->setName('S1');
        // Tes setters prennent \DateTimeInterface ; pour éviter l'ancien warning DBAL sur "date" on met \DateTime (mutable)
        $semester->setStartDate(new \DateTime('2024-09-01'));
        $semester->setEndDate(new \DateTime('2025-01-15'));
        $em->persist($semester);

        // ========= UE (CourseUnit) =========
        $cu = new CourseUnit();
        $cu->setName('Programmation');
        $cu->setAverage(12.5);
        $cu->setAverageScore(12.5);
        $cu->setSemester($semester);
        $cu->setCategory($category);
        // ⚠️ Ici ta méthode attend un seul Level (signature: setLevels(?Level $levels))
        $cu->setLevels($level);
        $em->persist($cu);

        // ========= Cours =========
        $course = new Course();
        $course->setName('PHP');
        $course->setAverage(13.0);
        $course->setCourseUnit($cu);
        $em->persist($course);

        if (method_exists($course, 'setSemester')) {
            $course->setSemester($semester);
        }
        
        if (method_exists($course, 'addClasse')) {
            $course->addClasse($classe);
        } elseif (method_exists($course, 'addClass')) {
            $course->addClass($classe);
        }

        // ========= Étudiants =========
        $student = new Student();
        $student->setClasse($classe);
        $student->setUser($test);
        if (method_exists($student, 'addSemester')) {
            $student->addSemester($semester);
        }
        $em->persist($student);

        $student2Entity = new Student();
        $student2Entity->setClasse($classe);
        $student2Entity->setUser($student2);
        if (method_exists($student2Entity, 'addSemester')) {
            $student2Entity->addSemester($semester);
        }
        $em->persist($student2Entity);

        // ========= Notes =========
        $grade = new Grade();
        $grade->setTitle('Interro 1');
        $grade->setGrade(15.5);
        $grade->setDividor(20);
        $grade->setStudent($student);
        $grade->setCourse($course);
        $grade->setCreatedAt(new \DateTimeImmutable('2024-10-10 10:00:00'));
        $em->persist($grade);

        $grade2 = new Grade();
        $grade2->setTitle('TP 1');
        $grade2->setGrade(12.0);
        $grade2->setDividor(20);
        $grade2->setStudent($student2Entity);
        $grade2->setCourse($course);
        $grade2->setCreatedAt(new \DateTimeImmutable('2024-10-11 14:30:00'));
        $em->persist($grade2);

        // ========= Sessions de cours =========
        $session = new CourseSession();
        $session->setCourse($course);
        $session->setClasse($classe);
        $session->setProfessor($professor);
        $session->setRoom('B204');
        $session->setStartAt(new \DateTimeImmutable('2024-10-11 10:00:00'));
        $session->setEndAt(new \DateTimeImmutable('2024-10-11 12:00:00'));
        $em->persist($session);

        $session2 = new CourseSession();
        $session2->setCourse($course);
        $session2->setClasse($classe);
        $session2->setProfessor($professor2);
        $session2->setRoom('A101');
        $session2->setStartAt(new \DateTimeImmutable('2024-10-12 14:00:00'));
        $session2->setEndAt(new \DateTimeImmutable('2024-10-12 16:00:00'));
        $em->persist($session2);

        // ========= Absences =========
        $absence = new Absence();
        $absence->setStudent($student);
        $absence->setSemester($semester);
        $absence->setStartedDate(new \DateTime('2024-10-10 09:00:00'));
        $absence->setEndedDate(new \DateTime('2024-10-10 12:00:00'));
        $absence->setJustified(false);
        $absence->setJustification('Non justifiée');
        if (method_exists($absence, 'setCourseSession')) {
            $absence->setCourseSession($session);
        }
        $em->persist($absence);

        $absence2 = new Absence();
        $absence2->setStudent($student2Entity);
        $absence2->setSemester($semester);
        $absence2->setStartedDate(new \DateTime('2024-10-12 14:00:00'));
        $absence2->setEndedDate(new \DateTime('2024-10-12 16:00:00'));
        $absence2->setJustified(true);
        $absence2->setJustification('Maladie');
        if (method_exists($absence2, 'setCourseSession')) {
            $absence2->setCourseSession($session2);
        }
        $em->persist($absence2);

        // ========= Liaisons Professor-Course =========
        if (method_exists($course, 'addProfessor')) {
            $course->addProfessor($professor);
            $course->addProfessor($professor2);
        }

        $em->flush();
    }
}