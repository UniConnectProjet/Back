<?php

namespace App\Entity;

use App\Repository\AbsenceRepository;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AbsenceRepository::class)]
class Absence
{
    // Statuts du workflow
    public const STATUS_UNJUSTIFIED = 'UNJUSTIFIED'; // pas encore déposée
    public const STATUS_PENDING     = 'PENDING';     // déposée, en attente admin
    public const STATUS_APPROVED    = 'APPROVED';    // acceptée par admin
    public const STATUS_REJECTED    = 'REJECTED';    // rejetée par admin
    
    // Statuts de présence
    public const STATUS_PRESENT = 'PRESENT';         // présent
    public const STATUS_ABSENT  = 'ABSENT';          // absent
    public const STATUS_LATE    = 'LATE';            // en retard

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

    #[ORM\ManyToOne(inversedBy: 'absences')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CourseSession $courseSession = null;

    // --- Justification déposée par l'étudiant ---
    #[ORM\Column(length: 20, options: ['default' => self::STATUS_UNJUSTIFIED])]
    private string $status = self::STATUS_UNJUSTIFIED;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $justificationReason = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $justificationComment = null;

    /**
     * Liste d'objets simples {name, path, size, mime}
     * (on ne stocke pas le chemin absolu)
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $justificationFiles = [];

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $justifiedAt = null;

    #[ORM\ManyToOne(targetEntity: \App\Entity\User::class)]
    private ?\App\Entity\User $justifiedBy = null;

    // --- Décision d'admin ---
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $reviewComment = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $reviewedAt = null;

    #[ORM\ManyToOne(targetEntity: \App\Entity\User::class)]
    private ?\App\Entity\User $reviewedBy = null;

    // --- Champs pour l'appel ---
    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    #[Groups(['getAllAbsences', 'getAllStudents', 'getStudentAbsences'])]
    private ?string $presenceStatus = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Groups(['getAllAbsences', 'getAllStudents', 'getStudentAbsences'])]
    private ?int $minutesLate = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['getAllAbsences', 'getAllStudents', 'getStudentAbsences'])]
    private ?string $justificationNote = null;

    #[ORM\ManyToOne(targetEntity: \App\Entity\User::class)]
    private ?\App\Entity\User $recordedBy = null;


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
        
        // Synchroniser le status avec le champ justified
        if ($justified === true) {
            $this->status = self::STATUS_APPROVED;
        } else {
            // Si justified = false, on garde le status actuel sauf s'il était APPROVED
            if ($this->status === self::STATUS_APPROVED) {
                $this->status = self::STATUS_UNJUSTIFIED;
            }
        }

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

    public function getCourseSession(): ?CourseSession
    {
        return $this->courseSession;
    }

    public function setCourseSession(?CourseSession $courseSession): static
    {
        $this->courseSession = $courseSession;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        
        // Synchroniser le champ justified avec le status
        if (property_exists($this, 'justified')) {
            $this->justified = ($status === self::STATUS_APPROVED);
        }
        
        return $this;
    }

    public function getJustificationReason(): ?string
    {
        return $this->justificationReason;
    }

    public function setJustificationReason(?string $reason): static
    {
        $this->justificationReason = $reason;
        return $this;
    }

    public function getJustificationComment(): ?string
    {
        return $this->justificationComment;
    }

    public function setJustificationComment(?string $comment): static
    {
        $this->justificationComment = $comment;
        return $this;
    }

    public function getJustificationFiles(): ?array
    {
        return $this->justificationFiles;
    }

    public function setJustificationFiles(?array $files): static
    {
        $this->justificationFiles = $files;
        return $this;
    }

    public function getJustifiedAt(): ?\DateTime
    {
        return $this->justifiedAt;
    }

    public function setJustifiedAt(?\DateTime $at): static
    {
        $this->justifiedAt = $at;
        return $this;
    }

    public function getReviewedAt(): ?\DateTime
    {
        return $this->reviewedAt;
    }

    public function setReviewedAt(?\DateTime $at): static
    {
        $this->reviewedAt = $at;
        return $this;
    }

    public function getJustifiedBy(): ?\App\Entity\User
    {
        return $this->justifiedBy;
    }
        
    public function setJustifiedBy(?\App\Entity\User $by): static
    {
        $this->justifiedBy = $by;
        return $this;
    }

    public function getMinutesLate(): ?int
    {
        return $this->minutesLate;
    }

    public function setMinutesLate(?int $minutesLate): static
    {
        $this->minutesLate = $minutesLate;
        return $this;
    }

    public function getJustificationNote(): ?string
    {
        return $this->justificationNote;
    }

    public function setJustificationNote(?string $justificationNote): static
    {
        $this->justificationNote = $justificationNote;
        return $this;
    }

    public function getRecordedBy(): ?\App\Entity\User
    {
        return $this->recordedBy;
    }

    public function setRecordedBy(?\App\Entity\User $recordedBy): static
    {
        $this->recordedBy = $recordedBy;
        return $this;
    }

    public function getPresenceStatus(): ?string
    {
        return $this->presenceStatus;
    }

    public function setPresenceStatus(?string $presenceStatus): static
    {
        $this->presenceStatus = $presenceStatus;
        return $this;
    }
}
