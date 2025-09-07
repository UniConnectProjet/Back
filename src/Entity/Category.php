<?php

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['category:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['category:read'])]
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

    /**
     * @var Collection<int, Level>
     */
    #[ORM\ManyToMany(targetEntity: Level::class, inversedBy: 'categories')]
    private Collection $levelId;

    /**
     * @var Collection<int, Professor>
     */
    #[ORM\ManyToMany(targetEntity: Professor::class, mappedBy: 'categories')]
    private Collection $professors;

    public function __construct()
    {
        $this->classes = new ArrayCollection();
        $this->courseUnits = new ArrayCollection();
        $this->levelId = new ArrayCollection();
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

    /**
     * @return Collection<int, Level>
     */
    public function getLevelId(): Collection
    {
        return $this->levelId;
    }

    public function addLevelId(Level $levelId): static
    {
        if (!$this->levelId->contains($levelId)) {
            $this->levelId->add($levelId);
        }

        return $this;
    }

    public function removeLevelId(Level $levelId): static
    {
        $this->levelId->removeElement($levelId);

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
            $professor->addCategory($this);
        }

        return $this;
    }

    public function removeProfessor(Professor $professor): static
    {
        if ($this->professors->removeElement($professor)) {
            $professor->removeCategory($this);
        }

        return $this;
    }
}
