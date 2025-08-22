<?php

namespace App\Tests\Entity;

use App\Entity\Absence;
use App\Entity\Student;
use App\Entity\Semester;
use PHPUnit\Framework\TestCase;

class AbsenceTest extends TestCase
{
    public function testFieldsAndRelations(): void
    {
        $absence = new Absence();
        $start = new \DateTime('2024-04-01');
        $end = new \DateTime('2024-04-03');

        $absence->setStartedDate($start);
        $absence->setEndedDate($end);
        $absence->setJustified(true);
        $absence->setJustification('Maladie');

        $student = new Student();
        $semester = new Semester();

        $absence->setStudent($student);
        $absence->setSemester($semester);

        $this->assertSame($start, $absence->getStartedDate());
        $this->assertSame($end, $absence->getEndedDate());
        $this->assertTrue($absence->isJustified());
        $this->assertEquals('Maladie', $absence->getJustification());
        $this->assertSame($student, $absence->getStudent());
        $this->assertSame($semester, $absence->getSemester());
    }
}
