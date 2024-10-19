<?php

namespace App\Entity;

use App\Repository\AbsenceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AbsenceRepository::class)]
class Absence
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $startedDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $endedDate = null;

    #[ORM\Column]
    private ?bool $justified = null;

    #[ORM\Column(length: 255)]
    private ?string $justification = null;

    #[ORM\ManyToOne(inversedBy: 'absences')]
    private ?Student $student = null;

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
}
