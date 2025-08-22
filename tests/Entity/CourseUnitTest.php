<?php

namespace App\Tests\Entity;

use App\Entity\CourseUnit;
use App\Entity\Course;
use App\Entity\Category;
use App\Entity\Semester;
use App\Entity\Level;
use PHPUnit\Framework\TestCase;

class CourseUnitTest extends TestCase
{
    public function testFields(): void
    {
        $unit = new CourseUnit();
        $unit->setName('UE1');
        $unit->setAverage(12.5);
        $unit->setAverageScore(14.0);

        $this->assertEquals('UE1', $unit->getName());
        $this->assertEquals(12.5, $unit->getAverage());
        $this->assertEquals(14.0, $unit->getAverageScore());
    }

    public function testRelations(): void
    {
        $unit = new CourseUnit();
        $semester = new Semester();
        $category = new Category();
        $level = new Level();

        $unit->setSemester($semester);
        $unit->setCategory($category);
        $unit->setLevels($level);

        $this->assertSame($semester, $unit->getSemester());
        $this->assertSame($category, $unit->getCategory());
        $this->assertSame($level, $unit->getLevels());
    }
}
