<?php

namespace App\Entity;

use App\Repository\AbsenceRepository;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AbsenceRepository::class)]
class Absence
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['getAllAbsences', 'getAllStudents', 'getStudentAbsences'])]
    private ?\DateTimeInterface $startedDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['getAllAbsences', 'getAllStudents', 'getStudentAbsences'])]
    private ?\DateTimeInterface $endedDate = null;

    #[ORM\Column]
    #[Groups(['getAllAbsences', 'getAllStudents', 'getStudentAbsences'])]
    private ?bool $justified = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['getAllAbsences'])]
    private ?string $justification = null;

    #[ORM\ManyToOne(inversedBy: 'absences')]
    private ?Student $student = null;

    #[ORM\ManyToOne(inversedBy: 'absences')]
    private ?Semester $semester = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStartedDate(): ?\DateTimeInterface
    {
        return $this->startedDate;
    }

    public function setStartedDate(\DateTimeInterface $startedDate): static
    {
        $this->startedDate = $startedDate;

        return $this;
    }

    public function getEndedDate(): ?\DateTimeInterface
    {
        return $this->endedDate;
    }

    public function setEndedDate(\DateTimeInterface $endedDate): static
    {
        $this->endedDate = $endedDate;

        return $this;
    }

    public function isJustified(): ?bool
    {
        return $this->justified;
    }

    public function setJustified(bool $justified): static
    {
        $this->justified = $justified;

        return $this;
    }

    public function getJustification(): ?string
    {
        return $this->justification;
    }

    public function setJustification(string $justification): static
    {
        $this->justification = $justification;

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

    public function getSemester(): ?Semester
    {
        return $this->semester;
    }

    public function setSemester(?Semester $semester): static
    {
        $this->semester = $semester;

        return $this;
    }
}
