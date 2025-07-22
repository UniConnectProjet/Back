<?php

namespace App\Tests\Entity;

use App\Entity\Semester;
use App\Entity\Student;
use App\Entity\Classe;
use App\Entity\CourseUnit;
use App\Entity\Absence;
use PHPUnit\Framework\TestCase;

class SemesterTest extends TestCase
{
    public function testSetAndGetName(): void
    {
        $semester = new Semester();
        $semester->setName('Semestre 1');

        $this->assertEquals('Semestre 1', $semester->getName());
    }

    public function testSetAndGetStartDate(): void
    {
        $semester = new Semester();
        $startDate = new \DateTime('2024-01-01');
        $semester->setStartDate($startDate);

        $this->assertSame($startDate, $semester->getStartDate());
    }

    public function testSetAndGetEndDate(): void
    {
        $semester = new Semester();
        $endDate = new \DateTime('2024-06-30');
        $semester->setEndDate($endDate);

        $this->assertSame($endDate, $semester->getEndDate());
    }

    public function testAddAndRemoveStudent(): void
    {
        $semester = new Semester();
        $student = new Student();
        $semester->addStudent($student);

        $this->assertTrue($semester->getStudents()->contains($student));

        $semester->removeStudent($student);
        $this->assertFalse($semester->getStudents()->contains($student));
    }

    public function testAddAndRemoveClasse(): void
    {
        $semester = new Semester();
        $classe = new Classe();
        $semester->addClass($classe);

        $this->assertTrue($semester->getClasses()->contains($classe));

        $semester->removeClass($classe);
        $this->assertFalse($semester->getClasses()->contains($classe));
    }

    public function testAddAndRemoveCourseUnit(): void
    {
        $semester = new Semester();
        $courseUnit = new CourseUnit();
        $semester->addCourseUnit($courseUnit);

        $this->assertTrue($semester->getCourseUnits()->contains($courseUnit));

        $semester->removeCourseUnit($courseUnit);
        $this->assertFalse($semester->getCourseUnits()->contains($courseUnit));
    }

    public function testAddAndRemoveAbsence(): void
    {
        $semester = new Semester();
        $absence = new Absence();
        $semester->addAbsence($absence);

        $this->assertTrue($semester->getAbsences()->contains($absence));

        $semester->removeAbsence($absence);
        $this->assertFalse($semester->getAbsences()->contains($absence));
    }
}