<?php

declare(strict_types=1);

namespace App\Entity;

use App\Doctrine\TimestampableTrait;
use App\Repository\DossierRepository;
use App\ValueObject\DossierUploadStatus;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinTable;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Uid\Uuid;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
#[ORM\Entity(repositoryClass: DossierRepository::class)]
#[UniqueEntity('dossierNr')]
#[ORM\HasLifecycleCallbacks]
class Dossier implements EntityWithId
{
    use TimestampableTrait;

    public const STATUS_CONCEPT = 'concept';                // Dossier is just uploaded and does not have (all) the documents present yet
    public const STATUS_COMPLETED = 'completed';            // Dossier has all the uploaded documents and is ready for publication
    public const STATUS_PREVIEW = 'preview';                // Dossier is in preview mode and can only be viewed with specific tokens
    public const STATUS_PUBLISHED = 'published';            // Dossier is published and available for everybody
    public const STATUS_RETRACTED = 'retracted';            // Dossier is retracted (but not deleted) and not available for anybody

    public const DECISION_ALREADY_PUBLIC = 'already_public';
    public const DECISION_PUBLIC = 'public';
    public const DECISION_PARTIAL_PUBLIC = 'partial_public';
    public const DECISION_NOT_PUBLIC = 'not_public';
    public const DECISION_NOTHING_FOUND = 'nothing_found';

    public const REASON_WOB_REQUEST = 'wob_request';
    public const REASON_WOO_REQUEST = 'woo_request';
    public const REASON_WOO_ACTIVE = 'woo_active';

    // This is a list of all allowed state changes. Note that state changes still can have additional
    // criteria that must be met (ie: concept -> ready needs all documents to be present etc)
    /** @var array<string, array<string>> */
    public array $allowedStates = [
        Dossier::STATUS_CONCEPT => [Dossier::STATUS_COMPLETED],
        Dossier::STATUS_COMPLETED => [Dossier::STATUS_PREVIEW, Dossier::STATUS_CONCEPT],
        Dossier::STATUS_PREVIEW => [Dossier::STATUS_RETRACTED, Dossier::STATUS_CONCEPT, Dossier::STATUS_PUBLISHED],
        Dossier::STATUS_PUBLISHED => [Dossier::STATUS_RETRACTED],
        Dossier::STATUS_RETRACTED => [Dossier::STATUS_CONCEPT],
    ];

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private ?Uuid $id = null;

    /** @var Collection|Document[] */
    #[ORM\ManyToMany(targetEntity: Document::class, mappedBy: 'dossiers')]
    #[ORM\OrderBy(['documentNr' => 'ASC'])]
    private Collection $documents;

    #[ORM\Column(length: 255, unique: true)]
    private string $dossierNr;

    #[ORM\Column(length: 500)]
    private string $title;

    #[ORM\Column(length: 255)]
    private string $status;

    /** @var Collection|Department[] */
    #[ORM\ManyToMany(targetEntity: Department::class)]
    private Collection $departments;

    /** @var Collection|GovernmentOfficial[] */
    #[ORM\ManyToMany(targetEntity: GovernmentOfficial::class)]
    private Collection $governmentOfficials;

    #[ORM\Column(type: Types::TEXT)]
    private string $summary;

    #[ORM\Column(length: 255)]
    private string $documentPrefix;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dateFrom = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dateTo = null;

    #[ORM\Column(length: 255, nullable: false)]
    private string $publicationReason;

    #[ORM\Column(length: 255, nullable: false)]
    private string $decision;

    /** @var Collection|Inquiry[] */
    #[ORM\ManyToMany(targetEntity: Inquiry::class, mappedBy: 'dossiers')]
    #[JoinTable(name: 'inquiry_dossier')]
    private Collection $inquiries;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $publicationDate;

    #[ORM\OneToOne(mappedBy: 'dossier', targetEntity: Inventory::class)]
    private ?Inventory $inventory = null;

    #[ORM\OneToOne(mappedBy: 'dossier', targetEntity: RawInventory::class)]
    private ?RawInventory $rawInventory = null;

    #[ORM\OneToOne(mappedBy: 'dossier', targetEntity: DecisionDocument::class)]
    private ?DecisionDocument $decisionDocument = null;

    /**
     * @var string[]|null
     */
    #[ORM\Column(nullable: true)]
    private ?array $defaultSubjects = null;

    public function __construct()
    {
        $this->documents = new ArrayCollection();
        $this->departments = new ArrayCollection();
        $this->governmentOfficials = new ArrayCollection();
        $this->inquiries = new ArrayCollection();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getDossierNr(): string
    {
        return $this->dossierNr;
    }

    public function setDossierNr(string $dossierNr): self
    {
        $this->dossierNr = strtolower($dossierNr);

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        if ($status === self::STATUS_PUBLISHED) {
            $this->publicationDate = new \DateTimeImmutable();
        }

        if ($status === self::STATUS_RETRACTED) {
            $this->publicationDate = null;
        }

        $this->status = $status;

        return $this;
    }

    public function getUploadStatus(): DossierUploadStatus
    {
        return new DossierUploadStatus($this);
    }

    /**
     * @return Collection|Department[]
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

    /**
     * @return Collection|GovernmentOfficial[]
     */
    public function getGovernmentOfficials(): Collection
    {
        return $this->governmentOfficials;
    }

    public function addGovernmentOfficial(GovernmentOfficial $governmentOfficial): self
    {
        if (! $this->governmentOfficials->contains($governmentOfficial)) {
            $this->governmentOfficials->add($governmentOfficial);
        }

        return $this;
    }

    public function removeGovernmentOfficial(GovernmentOfficial $governmentOfficial): self
    {
        $this->governmentOfficials->removeElement($governmentOfficial);

        return $this;
    }

    public function getSummary(): string
    {
        return $this->summary;
    }

    public function setSummary(string $summary): self
    {
        $this->summary = $summary;

        return $this;
    }

    public function getDocumentPrefix(): string
    {
        return $this->documentPrefix;
    }

    public function setDocumentPrefix(string $documentPrefix): self
    {
        $this->documentPrefix = $documentPrefix;

        return $this;
    }

    /**
     * @return Collection|Document[]
     */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    public function getInventory(): ?Inventory
    {
        return $this->inventory;
    }

    public function setInventory(?Inventory $inventory): self
    {
        $this->inventory = $inventory;

        return $this;
    }

    public function addDocument(Document $document): self
    {
        if (! $this->documents->contains($document)) {
            $this->documents->add($document);
            $document->addDossier($this);
        }

        return $this;
    }

    public function removeDocument(Document $document): self
    {
        $this->documents->removeElement($document);
        $document->removeDossier($this);

        return $this;
    }

    public function isAllowedState(string $newState): bool
    {
        $currentState = $this->getStatus();
        if (! isset($this->allowedStates[$currentState]) || ! in_array($newState, $this->allowedStates[$currentState])) {
            return false;
        }

        return true;
    }

    public function getDateFrom(): ?\DateTimeImmutable
    {
        return $this->dateFrom;
    }

    public function setDateFrom(?\DateTimeImmutable $dateFrom): static
    {
        $this->dateFrom = $dateFrom;

        return $this;
    }

    public function getDateTo(): ?\DateTimeImmutable
    {
        return $this->dateTo;
    }

    public function setDateTo(?\DateTimeImmutable $dateTo): static
    {
        $this->dateTo = $dateTo;

        return $this;
    }

    public function getPublicationReason(): string
    {
        return $this->publicationReason;
    }

    public function setPublicationReason(string $publicationReason): static
    {
        $this->publicationReason = $publicationReason;

        return $this;
    }

    public function pagecount(): int
    {
        $count = 0;
        foreach ($this->documents as $document) {
            $count += $document->getPagecount();
        }

        return $count;
    }

    public function getDecision(): string
    {
        return $this->decision;
    }

    public function setDecision(string $decision): static
    {
        $this->decision = $decision;

        return $this;
    }

    /**
     * @return Collection|Inquiry[]
     */
    public function getInquiries(): Collection
    {
        return $this->inquiries;
    }

    public function addInquiry(Inquiry $inquiry): self
    {
        if (! $this->inquiries->contains($inquiry)) {
            $this->inquiries->add($inquiry);
            $inquiry->addDossier($this);
        }

        return $this;
    }

    public function getPublicationDate(): ?\DateTimeImmutable
    {
        return $this->publicationDate;
    }

    public function setPublicationDate(?\DateTimeImmutable $publicationDate): void
    {
        $this->publicationDate = $publicationDate;
    }

    public function getDecisionDocument(): ?DecisionDocument
    {
        return $this->decisionDocument;
    }

    public function setDecisionDocument(?DecisionDocument $decisionDocument): self
    {
        $this->decisionDocument = $decisionDocument;

        return $this;
    }

    /**
     * @return string[]|null
     */
    public function getDefaultSubjects(): ?array
    {
        return $this->defaultSubjects;
    }

    /**
     * @param string[]|null $defaultSubjects
     *
     * @return $this
     */
    public function setDefaultSubjects(?array $defaultSubjects): static
    {
        $this->defaultSubjects = $defaultSubjects;

        return $this;
    }

    public function removeInquiry(Inquiry $inquiry): static
    {
        if ($this->inquiries->removeElement($inquiry)) {
            $inquiry->removeDossier($this);
        }

        return $this;
    }

    public function getRawInventory(): ?RawInventory
    {
        return $this->rawInventory;
    }

    public function setRawInventory(?RawInventory $rawInventory): static
    {
        // set the owning side of the relation if necessary
        if ($rawInventory !== null && $rawInventory->getDossier() !== $this) {
            $rawInventory->setDossier($this);
        }

        $this->rawInventory = $rawInventory;

        return $this;
    }

    public function needsInventoryAndDocuments(): bool
    {
        return $this->getDecision() !== self::DECISION_NOTHING_FOUND
            && $this->getDecision() !== self::DECISION_NOT_PUBLIC;
    }
}
