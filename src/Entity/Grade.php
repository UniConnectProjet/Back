<?php

namespace App\Entity;

use App\Repository\GradeRepository;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GradeRepository::class)]
class Grade
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    #[Groups(['getAllGrades'])]
    private ?float $grade = null;

    #[ORM\Column]
    #[Groups(['getAllGrades'])]
    private ?float $dividor = null;

    #[ORM\Column(length: 255)]
    #[Groups(['getAllGrades'])]
    private ?string $title = null;

    #[ORM\ManyToOne(inversedBy: 'grades')]
    private ?Student $student = null;

    #[ORM\ManyToOne(inversedBy: 'grades')]
    private ?Course $course = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGrade(): ?float
    {
        return $this->grade;
    }

    public function setGrade(float $grade): static
    {
        $this->grade = $grade;

        return $this;
    }

    public function getDividor(): ?float
    {
        return $this->dividor;
    }

    public function setDividor(float $dividor): static
    {
        $this->dividor = $dividor;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getStudent(): ?Student
    {
        return $this->student;
    }

    public function setStudent(?Student $student): static
    {
        $this->student = $student;

        return $this;
    }

    public function getCourse(): ?Course
    {
        return $this->course;
    }

    public function setCourse(?Course $course): static
    {
        $this->course = $course;

        return $this;
    }
}
