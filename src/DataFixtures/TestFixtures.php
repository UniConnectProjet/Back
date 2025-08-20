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
        $admin->setName('Test');
        $admin->setLastname('User');
        $admin->setBirthday(new \DateTime('2000-01-01')); // facultatif, mais utile pour les tests
        $em->persist($admin);

        $prof = new User();
        $prof->setEmail('prof@example.com');
        $prof->setRoles(['ROLE_PROF']);
        $prof->setPassword($this->hasher->hashPassword($prof, 'prof'));
        $prof->setName('Test');
        $prof->setLastname('User');
        $prof->setBirthday(new \DateTime('2000-01-01'));
        $em->persist($prof);

        $test = new User();
        $test->setEmail('test@example.com');
        $test->setRoles(['ROLE_USER','ROLE_ADMIN']); // utile pour les tests
        $test->setName('Test');
        $test->setLastname('User');
        $test->setPassword($this->hasher->hashPassword($test, 'test'));
        $test->setBirthday(new \DateTime('2000-01-01'));
        $em->persist($test);

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
        // D’après ton entité, le setter est bien setLevelId(?Level)
        $classe->setLevelId($level);
        $classe->setCategory($category);
        $em->persist($classe);

        // ========= Semestre =========
        $semester = new Semester();
        $semester->setName('S1');
        // Tes setters prennent \DateTimeInterface ; pour éviter l’ancien warning DBAL sur "date" on met \DateTime (mutable)
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

        // ========= Étudiant =========
        $student = new Student();
        $student->setClasse($classe);
        $student->setUser($test);
        $student->getSemesters($semester);
        // côté inverse géré par User::setStudent(), mais on force la cohérence si besoin :
        $test->setStudent($student);
        $em->persist($student);

        // ========= Note =========
        $grade = new Grade();
        $grade->setTitle('Interro 1');
        $grade->setGrade(15.5);
        $grade->setDividor(20);
        $grade->setStudent($student);
        $grade->setCourse($course);
        $em->persist($grade);

        // ========= Absence =========
        $absence = new Absence();
        $absence->setStudent($student);
        $absence->setSemester($semester);
        // Absence::setStartedDate/EndedDate acceptent DateTimeInterface → on met Immutable
        $absence->setStartedDate(new \DateTime('2024-10-10 09:00:00'));
        $absence->setEndedDate(new \DateTime('2024-10-10 12:00:00'));
        $absence->setJustified(false);
        $absence->setJustification('Non justifiée');
        $em->persist($absence);

        // ========= Séance de cours =========
        $session = new CourseSession();
        $session->setCourse($course);
        $session->setClasse($classe);
        $session->setProfessor($prof);
        $session->setRoom('B204');
        // Dans l’entité CourseSession, setStartAt/setEndAt attendent  DateTime
        $session->setStartAt(new \DateTimeImmutable('2024-10-11 10:00:00'));
        $session->setEndAt(new \DateTimeImmutable('2024-10-11 12:00:00'));
        $em->persist($session);

        $em->flush();
    }
}