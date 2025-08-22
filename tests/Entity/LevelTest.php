<?php

namespace App\Tests\Entity;

use App\Entity\Level;
use App\Entity\Classe;
use App\Entity\Category;
use App\Entity\CourseUnit;
use PHPUnit\Framework\TestCase;

class LevelTest extends TestCase
{
    public function testName(): void
    {
        $level = new Level();
        $level->setName('Licence 1');
        $this->assertEquals('Licence 1', $level->getName());
    }

    public function testAddAndRemoveClasse(): void
    {
        $level = new Level();
        $classe = new Classe();
        $level->addClass($classe);
        $this->assertTrue($level->getClasses()->contains($classe));

        $level->removeClass($classe);
        $this->assertFalse($level->getClasses()->contains($classe));
    }

    public function testAddAndRemoveCategory(): void
    {
        $level = new Level();
        $category = new Category();
        $level->addCategory($category);
        $this->assertTrue($level->getCategories()->contains($category));

        $level->removeCategory($category);
        $this->assertFalse($level->getCategories()->contains($category));
    }

    public function testAddAndRemoveCourseUnit(): void
    {
        $level = new Level();
        $unit = new CourseUnit();
        $level->addCourseUnit($unit);
        $this->assertTrue($level->getCourseUnits()->contains($unit));

        $level->removeCourseUnit($unit);
        $this->assertFalse($level->getCourseUnits()->contains($unit));
    }
}
