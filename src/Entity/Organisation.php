<?php

declare(strict_types=1);

namespace App\Entity;

use App\Doctrine\TimestampableTrait;
use App\Repository\OrganisationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: OrganisationRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['name'], message: 'This organisation already exists.')]
class Organisation
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private Uuid $id;

    #[ORM\Column(length: 255, unique: true, nullable: false)]
    private string $name;

    #[ORM\ManyToOne(inversedBy: 'organisations')]
    #[ORM\JoinColumn(nullable: false)]
    private Department $department;

    /** @var Collection|User[] */
    #[ORM\OneToMany(mappedBy: 'organisation', targetEntity: User::class)]
    private Collection $users;

    /** @var Collection|DocumentPrefix[] */
    #[ORM\OneToMany(mappedBy: 'organisation', targetEntity: DocumentPrefix::class)]
    private Collection $documentPrefixes;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->documentPrefixes = new ArrayCollection();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDepartment(): Department
    {
        return $this->department;
    }

    public function setDepartment(Department $department): static
    {
        $this->department = $department;

        return $this;
    }

    /** @return Collection|User[] */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (! $this->users->contains($user)) {
            $this->users->add($user);
            $user->setOrganisation($this);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        $this->users->removeElement($user);

        return $this;
    }

    /**
     * @return Collection|DocumentPrefix[]
     */
    public function getDocumentPrefixes(): Collection
    {
        return $this->documentPrefixes;
    }

    /**
     * @return string[]
     */
    public function getPrefixesAsArray(): array
    {
        return array_map(
            // @phpstan-ignore-next-line
            fn ($prefix) => $prefix->getPrefix(),
            $this->documentPrefixes->toArray()
        );
    }

    public function addDocumentPrefix(DocumentPrefix $documentPrefix): static
    {
        if (! $this->documentPrefixes->contains($documentPrefix)) {
            $this->documentPrefixes->add($documentPrefix);
            $documentPrefix->setOrganisation($this);
        }

        return $this;
    }

    public function removeDocumentPrefix(DocumentPrefix $documentPrefix): static
    {
        $this->documentPrefixes->removeElement($documentPrefix);

        return $this;
    }
}
