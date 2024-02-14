<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\DepartmentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: DepartmentRepository::class)]
#[ORM\UniqueConstraint(
    name: 'department_pk',
    columns: ['name']
)]
class Department
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private Uuid $id;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $shortTag = null;

    /** @var Collection|Organisation[] */
    #[ORM\OneToMany(mappedBy: 'department', targetEntity: Organisation::class)]
    private Collection $organisations;

    public function __construct()
    {
        $this->organisations = new ArrayCollection();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getShortTag(): ?string
    {
        return $this->shortTag;
    }

    public function setShortTag(?string $shortTag): static
    {
        $this->shortTag = $shortTag;

        return $this;
    }

    public function nameAndShort(): string
    {
        return $this->name . ' (' . $this->shortTag . ')';
    }

    /**
     * @return Collection<array-key,Organisation>
     */
    public function getOrganisations(): Collection
    {
        return $this->organisations;
    }

    public function addOrganisation(Organisation $organisation): static
    {
        if (! $this->organisations->contains($organisation)) {
            $this->organisations->add($organisation);
            $organisation->setDepartment($this);
        }

        return $this;
    }

    public function removeOrganisation(Organisation $organisation): static
    {
        $this->organisations->removeElement($organisation);

        return $this;
    }
}
