<?php

namespace App\Tests\Entity;

use App\Entity\Course;
use App\Entity\Classe;
use App\Entity\CourseUnit;
use App\Entity\Student;
use PHPUnit\Framework\TestCase;

class CourseTest extends TestCase
{
    public function testFields(): void
    {
        $course = new Course();
        $course->setName('Maths');
        $course->setAverage(13.0);

        $this->assertEquals('Maths', $course->getName());
        $this->assertEquals(13.0, $course->getAverage());
    }

    public function testRelations(): void
    {
        $course = new Course();
        $unit = new CourseUnit();
        $course->setCourseUnit($unit);

        $this->assertSame($unit, $course->getCourseUnit());
    }
}
