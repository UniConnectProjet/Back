<?php

namespace App\Tests\Entity;

use App\Entity\Grade;
use App\Entity\Course;
use App\Entity\Student;
use PHPUnit\Framework\TestCase;

class GradeTest extends TestCase
{
    public function testBasicFields(): void
    {
        $grade = new Grade();
        $grade->setGrade(18.5);
        $grade->setDividor(20);
        $grade->setTitle('Exam 1');

        $this->assertEquals(18.5, $grade->getGrade());
        $this->assertEquals(20, $grade->getDividor());
        $this->assertEquals('Exam 1', $grade->getTitle());
    }

    public function testRelations(): void
    {
        $grade = new Grade();
        $student = new Student();
        $course = new Course();

        $grade->setStudent($student);
        $grade->setCourse($course);

        $this->assertSame($student, $grade->getStudent());
        $this->assertSame($course, $grade->getCourse());
    }
}
