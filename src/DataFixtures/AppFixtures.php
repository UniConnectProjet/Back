<?php

namespace App\DataFixtures;

use App\Entity\Absence;
use App\Entity\Classe;
use App\Entity\Course;
use App\Entity\CourseUnit;
use App\Entity\Grade;
use App\Entity\Semester;
use App\Entity\Student;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // Créer des Classes
        $classes = [];
        for ($i = 0; $i < 5; $i++) {
            $classe = new Classe();
            $classe->setName($faker->word);
            $manager->persist($classe);
            $classes[] = $classe;
        }

        // Créer des Semestres
        $semesters = [];
        for ($i = 1; $i <= 2; $i++) {
            $semester = new Semester();
            $semester->setName("Semestre $i");
            $semester->setStartDate($faker->dateTimeThisYear);
            $semester->setEndDate($faker->dateTimeThisYear);
            $manager->persist($semester);
            $semesters[] = $semester;
        }

        // Créer des Users
        $users = [];
        for ($i = 0; $i < 50; $i++) { // Augmenté à 50 pour correspondre au nombre d'étudiants
            $user = new User();
            $user->setName($faker->firstName);
            $user->setLastname($faker->lastName);
            $user->setBirthday($faker->dateTimeThisCentury);
            $user->setEmail($faker->email);
            $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
            $user->setRoles(['ROLE_USER']);
            $manager->persist($user);
            $users[] = $user;
        }

        // Créer des Students et les lier aux Users
        $students = [];
        $studentsPerClass = 10;

        for ($i = 0; $i < count($users); $i++) {
            $student = new Student();

            // Répartition équitable : 0–9 dans classe[0], 10–19 dans classe[1], etc.
            $classIndex = intdiv($i, $studentsPerClass);
            $student->setClasse($classes[$classIndex]);

            $student->setUser($users[$i]); // Association de l'étudiant à l'utilisateur

            foreach ($semesters as $semester) {
                $student->addSemester($semester);
            }

            $manager->persist($student);
            $students[] = $student;
        }

        // Créer des CourseUnits (UE)
        $courseUnits = [];
        for ($i = 0; $i < 4; $i++) {
            $courseUnit = new CourseUnit();
            $courseUnit->setName($faker->word);
            $courseUnit->setSemester($semesters[array_rand($semesters)]);
            $courseUnit->setAverage(mt_rand(10, 20));
            $courseUnit->setAverageScore(mt_rand(10, 20));
            $manager->persist($courseUnit);
            $courseUnits[] = $courseUnit;
        }

        // Créer des Courses (Modules)
        $courses = [];
        foreach ($courseUnits as $courseUnit) {
            for ($i = 0; $i < 2; $i++) {
                $course = new Course();
                $course->setName($faker->word);
                $course->setAverage(mt_rand(10, 20));
                $course->setCourseUnit($courseUnit);
                $manager->persist($course);
                $courses[] = $course;
            }
        }

        // Créer des Grades (Notes)
        foreach ($students as $student) {
            foreach ($courses as $course) {
                $grade = new Grade();
                $grade->setStudent($student);
                $grade->setCourse($course);
                $grade->setTitle($faker->word);
                $grade->setGrade(mt_rand(10, 20));
                $grade->setDividor(mt_rand(10, 20));
                $manager->persist($grade);
            }
        }

        // Créer des Absences
        foreach ($students as $student) {
            $absence = new Absence();
            $absence->setStudent($student);
            $absence->setStartedDate($faker->dateTimeThisYear);
            $absence->setEndedDate($faker->dateTimeThisYear);
            $absence->setJustified($faker->boolean);
            if($absence->setJustified($faker->boolean) != false){
                $absence->setJustification($faker->sentence);
            }
            $absence->setSemester($semesters[array_rand($semesters)]);
            $manager->persist($absence);
        }

        $manager->flush();
    }
}