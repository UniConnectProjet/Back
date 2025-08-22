<?php

namespace App\Entity;

use App\Repository\LevelRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: LevelRepository::class)]
class Level
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['getAllLevels','getLevelsByCategory'])]
    private ?string $name = null;

    /**
     * @var Collection<int, Classe>
     */
    #[ORM\OneToMany(targetEntity: Classe::class, mappedBy: 'level_id')]
    private Collection $classes;

    /**
     * @var Collection<int, Category>
     */
    #[ORM\ManyToMany(targetEntity: Category::class, mappedBy: 'levelId')]
    private Collection $categories;

    /**
     * @var Collection<int, CourseUnit>
     */
    #[ORM\OneToMany(targetEntity: CourseUnit::class, mappedBy: 'levels')]
    private Collection $courseUnits;

    public function __construct()
    {
        $this->classes = new ArrayCollection();
        $this->categories = new ArrayCollection();
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
            $class->setLevelId($this);
        }

        return $this;
    }

    public function removeClass(Classe $class): static
    {
        if ($this->classes->removeElement($class)) {
            // set the owning side to null (unless already changed)
            if ($class->getLevelId() === $this) {
                $class->setLevelId(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Category>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(Category $category): static
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
            $category->addLevelId($this);
        }

        return $this;
    }

    public function removeCategory(Category $category): static
    {
        if ($this->categories->removeElement($category)) {
            $category->removeLevelId($this);
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
            $courseUnit->setLevels($this);
        }

        return $this;
    }

    public function removeCourseUnit(CourseUnit $courseUnit): static
    {
        if ($this->courseUnits->removeElement($courseUnit)) {
            // set the owning side to null (unless already changed)
            if ($courseUnit->getLevels() === $this) {
                $courseUnit->setLevels(null);
            }
        }

        return $this;
    }
}
