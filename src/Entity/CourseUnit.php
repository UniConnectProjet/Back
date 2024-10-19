<?php

namespace App\Entity;

use App\Repository\CourseUnitRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CourseUnitRepository::class)]
class CourseUnit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    private ?int $semester = null;

    #[ORM\Column]
    private ?float $average = null;

    /**
     * @var Collection<int, Course>
     */
    #[ORM\OneToMany(targetEntity: Course::class, mappedBy: 'courseUnit')]
    private Collection $courses;

    public function __construct()
    {
        $this->courses = new ArrayCollection();
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

    public function getSemester(): ?int
    {
        return $this->semester;
    }

    public function setSemester(int $semester): static
    {
        $this->semester = $semester;

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
     * @return Collection<int, Course>
     */
    public function getCourses(): Collection
    {
        return $this->courses;
    }

    public function addCourse(Course $course): static
    {
        if (!$this->courses->contains($course)) {
            $this->courses->add($course);
            $course->setCourseUnit($this);
        }

        return $this;
    }

    public function removeCourse(Course $course): static
    {
        if ($this->courses->removeElement($course)) {
            // set the owning side to null (unless already changed)
            if ($course->getCourseUnit() === $this) {
                $course->setCourseUnit(null);
            }
        }

        return $this;
    }
}
