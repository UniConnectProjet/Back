<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class CourseSession
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Course::class, inversedBy: 'sessions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Course $course = null;

    #[ORM\ManyToOne(targetEntity: Classe::class, inversedBy: 'sessions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Classe $classe = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $startAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $endAt;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $room = null;

    #[ORM\ManyToOne(inversedBy: 'courseSessions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Professor $professor = null;

    /**
     * @var Collection<int, Absence>
     */
    #[ORM\OneToMany(targetEntity: Absence::class, mappedBy: 'courseSession')]
    private Collection $absences;

    public function __construct()
    {
        $this->absences = new ArrayCollection();
    }

    public function getId(): ?int { 
        return $this->id; 
    }

    public function getCourse(): ?Course { 
        return $this->course; 
    }
    public function setCourse(?Course $course): self { 
        $this->course = $course; 
        return $this; 
    }

    public function getClasse(): ?Classe { 
        return $this->classe; 
    }
    public function setClasse(?Classe $classe): self { 
        $this->classe = $classe; 
        return $this; 
    }

    public function getStartAt(): \DateTimeImmutable { 
        return $this->startAt; 
    }
    public function setStartAt(\DateTimeImmutable $startAt): self { 
        $this->startAt = $startAt; 
        return $this; 
    }

    public function getEndAt(): \DateTimeImmutable { 
        return $this->endAt; 
    }
    public function setEndAt(\DateTimeImmutable $endAt): self { 
        $this->endAt = $endAt; 
        return $this; 
    }

    public function getRoom(): ?string { 
        return $this->room; 
    }
    public function setRoom(?string $room): self { 
        $this->room = $room; 
        return $this; 
    }

    public function getProfessor(): ?Professor
    {
        return $this->professor;
    }

    public function setProfessor(?Professor $professor): static
    {
        $this->professor = $professor;

        return $this;
    }

    /**
     * @return Collection<int, Absence>
     */
    public function getAbsences(): Collection
    {
        return $this->absences;
    }

    public function addAbsence(Absence $absence): static
    {
        if (!$this->absences->contains($absence)) {
            $this->absences->add($absence);
            $absence->setCourseSession($this);
        }

        return $this;
    }

    public function removeAbsence(Absence $absence): static
    {
        if ($this->absences->removeElement($absence)) {
            // set the owning side to null (unless already changed)
            if ($absence->getCourseSession() === $this) {
                $absence->setCourseSession(null);
            }
        }

        return $this;
    }
}