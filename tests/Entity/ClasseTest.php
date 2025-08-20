<?php

namespace App\Tests\Entity;

use App\Entity\Classe;
use App\Entity\Level;
use App\Entity\Category;
use PHPUnit\Framework\TestCase;

class ClasseTest extends TestCase
{
    public function testName(): void
    {
        $classe = new Classe();
        $classe->setName('Classe A');
        $this->assertEquals('Classe A', $classe->getName());
    }

    public function testLevelAndCategory(): void
    {
        $classe = new Classe();
        $level = new Level();
        $category = new Category();

        $classe->setLevelId($level);
        $classe->setCategory($category);

        $this->assertSame($level, $classe->getLevelId());
        $this->assertSame($category, $classe->getCategory());
    }
}
