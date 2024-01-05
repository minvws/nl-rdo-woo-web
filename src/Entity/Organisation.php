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
use Symfony\Component\Validator\Constraints as Assert;

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
    #[ORM\OneToMany(mappedBy: 'organisation', targetEntity: DocumentPrefix::class, cascade: ['persist'])]
    #[Assert\Valid]
    private Collection $documentPrefixes;

    /** @var Collection|Inquiry[] */
    #[ORM\OneToMany(mappedBy: 'organisation', targetEntity: Inquiry::class)]
    private Collection $inquiries;

    /** @var Collection|Dossier[] */
    #[ORM\OneToMany(mappedBy: 'organisation', targetEntity: Dossier::class)]
    private Collection $dossiers;

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
     * @return ArrayCollection|DocumentPrefix[]
     */
    public function getDocumentPrefixes(): ArrayCollection
    {
        $values = $this->documentPrefixes->filter(
            /** @phpstan-ignore-next-line */
            fn (DocumentPrefix $prefix) => ! $prefix->isArchived()
        )->getValues();

        // Create a new instance to reset keys, this is important for use in the CollectionType form field
        /** @var ArrayCollection|DocumentPrefix[] $collection */
        $collection = new ArrayCollection($values);

        return $collection;
    }

    /**
     * @return string[]
     */
    public function getPrefixesAsArray(): array
    {
        return array_map(
            // @phpstan-ignore-next-line
            fn ($prefix) => $prefix->getPrefix(),
            $this->getDocumentPrefixes()->toArray()
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
        // Archive (soft-delete) instead of actual removal, this prevents the prefix from being re-used
        $documentPrefix->archive();

        return $this;
    }

    /**
     * @param Collection|Inquiry[] $inquiries
     */
    public function setInquiries(Collection $inquiries): void
    {
        $this->inquiries = $inquiries;
    }

    /**
     * @param Collection|Dossier[] $dossiers
     */
    public function setDossiers(Collection $dossiers): void
    {
        $this->dossiers = $dossiers;
    }

    /**
     * @return Collection|Inquiry[]
     */
    public function getInquiries(): Collection
    {
        return $this->inquiries;
    }

    /**
     * @return Collection|Dossier[]
     */
    public function getDossiers(): Collection
    {
        return $this->dossiers;
    }
}
