<?php

namespace App\Entity;

use App\Repository\CourseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\CourseSession;

#[ORM\Entity(repositoryClass: CourseRepository::class)]
class Course
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['getAllCourses', 'getAllStudents', 'getStudentGrades', 'getCourseUnits'])]
    private ?string $name = null;

    #[ORM\Column]
    #[Groups(['getAllCourses', 'getAllStudents', 'getStudentGrades'])]
    private ?float $average = null;

    /**
     * @var Collection<int, Grade>
     */
    #[ORM\OneToMany(targetEntity: Grade::class, mappedBy: 'course')]
    private Collection $grades;

    #[ORM\ManyToOne(inversedBy: 'courses')]
    #[ORM\JoinColumn(nullable: true)] // Important : nullable pour permettre de dissocier un module
    private ?CourseUnit $courseUnit = null;

    /**
     * @var Collection<int, Classe>
     */
    #[ORM\ManyToMany(targetEntity: Classe::class, inversedBy: 'courses')]
    #[ORM\JoinTable(name: 'course_classe')]
    private Collection $classes;

    /**
     * @var Collection<int, Student>
     */
    #[ORM\ManyToMany(targetEntity: Student::class, mappedBy: 'courses')]
    private Collection $students;

    /**
     * @var Collection<int, CourseSession>
     */
    #[ORM\OneToMany(targetEntity: CourseSession::class, mappedBy: 'course')]
    private Collection $sessions;

    /**
     * @var Collection<int, Professor>
     */
    #[ORM\ManyToMany(targetEntity: Professor::class, mappedBy: 'courses')]
    private Collection $professors;

    public function __construct()
    {
        $this->grades = new ArrayCollection();
        $this->classes = new ArrayCollection();
        $this->students = new ArrayCollection();
        $this->sessions = new ArrayCollection();
        $this->professors = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getAverage(): ?float
    {
        return $this->average;
    }

    public function setAverage(float $average): static
    {
        $this->average = $average;

        return $this;
    }

    /**
     * @return Collection<int, Grade>
     */
    public function getGrades(): Collection
    {
        return $this->grades;
    }

    public function addGrade(Grade $grade): static
    {
        if (!$this->grades->contains($grade)) {
            $this->grades->add($grade);
            $grade->setCourse($this);
        }

        return $this;
    }

    public function removeGrade(Grade $grade): static
    {
        if ($this->grades->removeElement($grade)) {
            // set the owning side to null (unless already changed)
            if ($grade->getCourse() === $this) {
                $grade->setCourse(null);
            }
        }

        return $this;
    }

    public function getCourseUnit(): ?CourseUnit
    {
        return $this->courseUnit;
    }

    public function setCourseUnit(?CourseUnit $courseUnit): static
    {
        $this->courseUnit = $courseUnit;

        return $this;
    }

    /**
     * @return Collection<int, Classe>
     */
    public function getClasses(): Collection
    {
        return $this->classes;
    }

    public function addClass(Classe $class): static
    {
        if (!$this->classes->contains($class)) {
            $this->classes->add($class);
            $class->addCourse($this);
        }

        return $this;
    }

    public function removeClass(Classe $class): static
    {
        if ($this->classes->removeElement($class)) {
            $class->removeCourse($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Student>
     */
    public function getStudents(): Collection
    {
        return $this->students;
    }

    public function addStudent(Student $student): static
    {
        if (!$this->students->contains($student)) {
            $this->students->add($student);
            $student->addCourse($this);
        }

        return $this;
    }

    public function removeStudent(Student $student): static
    {
        if ($this->students->removeElement($student)) {
            $student->removeCourse($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, CourseSession>
     */
    public function getCourseSessions(): Collection
    {
        return $this->sessions;
    }

    public function addCourseSession(CourseSession $s): static
    {
        if (!$this->sessions->contains($s)) {
            $this->sessions->add($s);
            $s->setCourse($this);
        }

        return $this;
    }

    public function removeCourseSession(CourseSession $s): static
    {
        if ($this->sessions->removeElement($s)) {
            // set the owning side to null (unless already changed)
            if ($s->getCourse() === $this) {
                $s->setCourse(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Professor>
     */
    public function getProfessors(): Collection
    {
        return $this->professors;
    }

    public function addProfessor(Professor $professor): static
    {
        if (!$this->professors->contains($professor)) {
            $this->professors->add($professor);
            $professor->addCourse($this);
        }

        return $this;
    }

    public function removeProfessor(Professor $professor): static
    {
        if ($this->professors->removeElement($professor)) {
            $professor->removeCourse($this);
        }

        return $this;
    }
}
