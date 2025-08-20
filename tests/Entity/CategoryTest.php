<?php

namespace App\Tests\Entity;

use App\Entity\Category;
use PHPUnit\Framework\TestCase;

class CategoryTest extends TestCase
{
    public function testName(): void
    {
        $category = new Category();
        $category->setName('Science');

        $this->assertEquals('Science', $category->getName());
    }
}
