<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\DepartmentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DepartmentRepository::class)]
#[UniqueEntity('name')]
#[UniqueEntity('slug')]
#[UniqueEntity('shortTag')]
class Department
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private Uuid $id;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\Length(min: 2, max: 100)]
    private string $name;

    #[ORM\Column(length: 20, unique: true)]
    #[Assert\Length(min: 2, max: 10)]
    private string $shortTag;

    #[ORM\Column(length: 20, unique: true)]
    #[Assert\Sequentially([
        new Assert\Length(min: 2, max: 20),
        new Assert\Type(type: ['alnum'], message: 'use_only_letters_and_numbers'),
    ])]
    private string $slug;

    #[ORM\Column]
    private bool $public = false;

    /** @var Collection<array-key, Organisation> */
    #[ORM\ManyToMany(targetEntity: Organisation::class, mappedBy: 'departments', fetch: 'EXTRA_LAZY')]
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

    public function getShortTag(): string
    {
        return $this->shortTag;
    }

    public function setShortTag(string $shortTag): static
    {
        $this->shortTag = $shortTag;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = strtolower($slug);

        return $this;
    }

    public function isPublic(): bool
    {
        return $this->public;
    }

    public function setPublic(bool $public): self
    {
        $this->public = $public;

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
            $organisation->addDepartment($this);
        }

        return $this;
    }

    public function removeOrganisation(Organisation $organisation): static
    {
        $this->organisations->removeElement($organisation);

        return $this;
    }
}
