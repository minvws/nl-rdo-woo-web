<?php

declare(strict_types=1);

namespace App\Entity;

use App\Doctrine\TimestampableTrait;
use App\Domain\Publication\Dossier\DocumentPrefix;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\Inquiry;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\WooDecision;
use App\Domain\Publication\Subject\Subject;
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

    /** @var Collection<array-key,Department> */
    #[ORM\ManyToMany(targetEntity: Department::class, inversedBy: 'organisations')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\Count(min: 1, minMessage: 'at_least_one_department_required')]
    private Collection $departments;

    /** @var Collection<array-key,User> */
    #[ORM\OneToMany(mappedBy: 'organisation', targetEntity: User::class)]
    private Collection $users;

    /** @var Collection<array-key,DocumentPrefix> */
    #[ORM\OneToMany(mappedBy: 'organisation', targetEntity: DocumentPrefix::class, cascade: ['persist'])]
    #[Assert\Valid]
    #[Assert\Count(min: 1, minMessage: 'at_least_one_prefix_required')]
    private Collection $documentPrefixes;

    /** @var Collection<array-key,Inquiry> */
    #[ORM\OneToMany(mappedBy: 'organisation', targetEntity: Inquiry::class)]
    private Collection $inquiries;

    /** @var Collection<array-key,WooDecision> */
    #[ORM\OneToMany(mappedBy: 'organisation', targetEntity: WooDecision::class)]
    private Collection $dossiers;

    /** @var Collection<array-key,Subject> */
    #[ORM\OneToMany(mappedBy: 'organisation', targetEntity: Subject::class, cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['name' => 'ASC'])]
    private Collection $subjects;

    public function __construct()
    {
        $this->id = Uuid::v6();
        $this->users = new ArrayCollection();
        $this->documentPrefixes = new ArrayCollection();
        $this->departments = new ArrayCollection();
        $this->inquiries = new ArrayCollection();
        $this->dossiers = new ArrayCollection();
        $this->subjects = new ArrayCollection();
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

    /**
     * @return Collection<array-key,Department>
     */
    public function getDepartments(): Collection
    {
        return $this->departments;
    }

    public function addDepartment(Department $department): self
    {
        if (! $this->departments->contains($department)) {
            $this->departments->add($department);
        }

        return $this;
    }

    public function removeDepartment(Department $department): self
    {
        $this->departments->removeElement($department);

        return $this;
    }

    /** @return Collection<array-key,User> */
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
     * @return ArrayCollection<array-key,DocumentPrefix>
     */
    public function getDocumentPrefixes(): ArrayCollection
    {
        $values = $this->documentPrefixes
            ->filter(fn (DocumentPrefix $prefix): bool => ! $prefix->isArchived())
            ->getValues();

        // Create a new instance to reset keys, this is important for use in the CollectionType form field
        /** @var ArrayCollection<DocumentPrefix> $collection */
        $collection = new ArrayCollection($values);

        return $collection;
    }

    /**
     * @return array<string>
     */
    public function getPrefixesAsArray(): array
    {
        return array_map(
            fn (DocumentPrefix $prefix): string => $prefix->getPrefix(),
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
     * @param Collection<Inquiry> $inquiries
     */
    public function setInquiries(Collection $inquiries): void
    {
        $this->inquiries = $inquiries;
    }

    /**
     * @param Collection<WooDecision> $dossiers
     */
    public function setDossiers(Collection $dossiers): void
    {
        $this->dossiers = $dossiers;
    }

    /**
     * @return Collection<Inquiry>
     */
    public function getInquiries(): Collection
    {
        return $this->inquiries;
    }

    /**
     * @return Collection<WooDecision>
     */
    public function getDossiers(): Collection
    {
        return $this->dossiers;
    }

    /**
     * @return Collection<array-key,Subject>
     */
    public function getSubjects(): Collection
    {
        return $this->subjects;
    }
}
