<?php

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * @var Collection<int, Classe>
     */
    #[ORM\OneToMany(targetEntity: Classe::class, mappedBy: 'category')]
    private Collection $classes;

    /**
     * @var Collection<int, CourseUnit>
     */
    #[ORM\OneToMany(targetEntity: CourseUnit::class, mappedBy: 'category')]
    private Collection $courseUnits;

    public function __construct()
    {
        $this->classes = new ArrayCollection();
        $this->courseUnits = new ArrayCollection();
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
            $class->setCategory($this);
        }

        return $this;
    }

    public function removeClass(Classe $class): static
    {
        if ($this->classes->removeElement($class)) {
            // set the owning side to null (unless already changed)
            if ($class->getCategory() === $this) {
                $class->setCategory(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, CourseUnit>
     */
    public function getCourseUnits(): Collection
    {
        return $this->courseUnits;
    }

    public function addCourseUnit(CourseUnit $courseUnit): static
    {
        if (!$this->courseUnits->contains($courseUnit)) {
            $this->courseUnits->add($courseUnit);
            $courseUnit->setCategory($this);
        }

        return $this;
    }

    public function removeCourseUnit(CourseUnit $courseUnit): static
    {
        if ($this->courseUnits->removeElement($courseUnit)) {
            // set the owning side to null (unless already changed)
            if ($courseUnit->getCategory() === $this) {
                $courseUnit->setCategory(null);
            }
        }

        return $this;
    }
}
