<?php

namespace App\Entity;

use App\Repository\ProfessorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProfessorRepository::class)]
class Professor
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'professor', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $userId = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $weeklyAvailability = null;

    /**
     * @var Collection<int, CourseSession>
     */
    #[ORM\OneToMany(targetEntity: CourseSession::class, mappedBy: 'professor')]
    private Collection $courseSessions;

    public function __construct()
    {
        $this->courseSessions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): ?User
    {
        return $this->userId;
    }

    public function setUserId(User $userId): static
    {
        $this->userId = $userId;

        return $this;
    }

    public function getWeeklyAvailability(): ?array
    {
        return $this->weeklyAvailability;
    }

    public function setWeeklyAvailability(?array $weeklyAvailability): static
    {
        $this->weeklyAvailability = $weeklyAvailability;

        return $this;
    }

    /**
     * @return Collection<int, CourseSession>
     */
    public function getCourseSessions(): Collection
    {
        return $this->courseSessions;
    }

    public function addCourseSession(CourseSession $courseSession): static
    {
        if (!$this->courseSessions->contains($courseSession)) {
            $this->courseSessions->add($courseSession);
            $courseSession->setProfessor($this);
        }

        return $this;
    }

    public function removeCourseSession(CourseSession $courseSession): static
    {
        if ($this->courseSessions->removeElement($courseSession)) {
            // set the owning side to null (unless already changed)
            if ($courseSession->getProfessor() === $this) {
                $courseSession->setProfessor(null);
            }
        }

        return $this;
    }
}
