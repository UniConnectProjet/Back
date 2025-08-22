<?php

namespace App\Entity;

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

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'courseSessions')]
    private ?User $professor = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $startAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $endAt;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $room = null;

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

    public function getProfessor(): ?User { 
        return $this->professor; 
    }
    public function setProfessor(?User $professor): self { 
        $this->professor = $professor; 
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
}