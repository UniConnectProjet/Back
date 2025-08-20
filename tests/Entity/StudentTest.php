<?php

namespace App\Tests\Entity;

use App\Entity\Student;
use App\Entity\Classe;
use App\Entity\User;
use App\Entity\Grade;
use App\Entity\Absence;
use App\Entity\Semester;
use App\Entity\Course;
use PHPUnit\Framework\TestCase;

class StudentTest extends TestCase
{
    public function testSetAndGetClasse(): void
    {
        $student = new Student();
        $classe = new Classe();
        $student->setClasse($classe);

        $this->assertSame($classe, $student->getClasse());
    }

    public function testSetAndGetUser(): void
    {
        $student = new Student();
        $user = new User();
        $student->setUser($user);

        $this->assertSame($user, $student->getUser());
    }

    public function testAddAndRemoveGrade(): void
    {
        $student = new Student();
        $grade = new Grade();
        $student->addGrade($grade);

        $this->assertTrue($student->getGrades()->contains($grade));

        $student->removeGrade($grade);
        $this->assertFalse($student->getGrades()->contains($grade));
    }

    public function testAddAndRemoveAbsence(): void
    {
        $student = new Student();
        $absence = new Absence();
        $student->addAbsence($absence);

        $this->assertTrue($student->getAbsences()->contains($absence));

        $student->removeAbsence($absence);
        $this->assertFalse($student->getAbsences()->contains($absence));
    }

    public function testAddAndRemoveSemester(): void
    {
        $student = new Student();
        $semester = new Semester();
        $student->addSemester($semester);

        $this->assertTrue($student->getSemesters()->contains($semester));

        $student->removeSemester($semester);
        $this->assertFalse($student->getSemesters()->contains($semester));
    }

    public function testAddAndRemoveCourse(): void
    {
        $student = new Student();
        $course = new Course();
        $student->addCourse($course);

        $this->assertTrue($student->getCourses()->contains($course));

        $student->removeCourse($course);
        $this->assertFalse($student->getCourses()->contains($course));
    }
}
