<?php

declare(strict_types=1);

namespace App\Entity;

use App\Doctrine\TimestampableTrait;
use App\Enum\PublicationStatus;
use App\Repository\DossierRepository;
use App\ValueObject\DossierUploadStatus;
use App\ValueObject\TranslatableMessage;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinTable;
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
#[ORM\UniqueConstraint(name: 'dossier_unique_index', columns: ['dossier_nr', 'document_prefix'])]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(['dossierNr', 'documentPrefix'])]
class Dossier implements EntityWithId, EntityWithBatchDownload
{
    use TimestampableTrait;

    public const DECISION_ALREADY_PUBLIC = 'already_public';
    public const DECISION_PUBLIC = 'public';
    public const DECISION_PARTIAL_PUBLIC = 'partial_public';
    public const DECISION_NOT_PUBLIC = 'not_public';
    public const DECISION_NOTHING_FOUND = 'nothing_found';

    public const REASON_WOB_REQUEST = 'wob_request';
    public const REASON_WOO_REQUEST = 'woo_request';
    public const REASON_WOO_ACTIVE = 'woo_active';

    /**
     * This is a list of all allowed state changes. Note that state changes still can have additional
     * criteria that must be met (ie: concept -> ready needs all documents to be present etc).
     *
     * @var array<string,array<PublicationStatus>>
     */
    public array $allowedStates = [
        PublicationStatus::NEW->value => [PublicationStatus::CONCEPT],
        PublicationStatus::CONCEPT->value => [PublicationStatus::SCHEDULED, PublicationStatus::PREVIEW, PublicationStatus::PUBLISHED],
        PublicationStatus::SCHEDULED->value => [PublicationStatus::PREVIEW, PublicationStatus::PUBLISHED],
        PublicationStatus::PREVIEW->value => [PublicationStatus::RETRACTED, PublicationStatus::PUBLISHED],
        PublicationStatus::PUBLISHED->value => [PublicationStatus::RETRACTED],
        PublicationStatus::RETRACTED->value => [PublicationStatus::CONCEPT],
    ];

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    /** @var Collection<array-key, Document> */
    #[ORM\ManyToMany(targetEntity: Document::class, mappedBy: 'dossiers', fetch: 'EXTRA_LAZY')]
    #[ORM\OrderBy(['documentNr' => 'ASC'])]
    private Collection $documents;

    #[ORM\Column(length: 255)]
    private string $dossierNr = '';

    #[ORM\Column(length: 500)]
    private string $title;

    #[ORM\Column(length: 255, enumType: PublicationStatus::class)]
    private PublicationStatus $status;

    /** @var Collection<array-key,Department> */
    #[ORM\ManyToMany(targetEntity: Department::class)]
    private Collection $departments;

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

    /** @var Collection<array-key,Inquiry> */
    #[ORM\ManyToMany(targetEntity: Inquiry::class, mappedBy: 'dossiers')]
    #[JoinTable(name: 'inquiry_dossier')]
    private Collection $inquiries;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $publicationDate = null;

    #[ORM\OneToOne(mappedBy: 'dossier', targetEntity: Inventory::class)]
    private ?Inventory $inventory = null;

    #[ORM\OneToOne(mappedBy: 'dossier', targetEntity: RawInventory::class)]
    private ?RawInventory $rawInventory = null;

    #[ORM\OneToOne(mappedBy: 'dossier', targetEntity: DecisionDocument::class)]
    private ?DecisionDocument $decisionDocument = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $decisionDate = null;

    /**
     * @var string[]|null
     */
    #[ORM\Column(nullable: true)]
    private ?array $defaultSubjects = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $previewDate = null;

    #[ORM\Column(type: Types::BOOLEAN, nullable: false)]
    private bool $completed = false;

    #[ORM\OneToOne(mappedBy: 'dossier', targetEntity: InventoryProcessRun::class)]
    private ?InventoryProcessRun $processRun = null;

    #[ORM\ManyToOne(inversedBy: 'dossiers')]
    #[ORM\JoinColumn(nullable: false)]
    private Organisation $organisation;

    /** @var Collection<array-key, DecisionAttachment> */
    #[ORM\OneToMany(mappedBy: 'dossier', targetEntity: DecisionAttachment::class, orphanRemoval: true)]
    private Collection $decisionAttachments;

    public function __construct()
    {
        $this->id = Uuid::v6();
        $this->status = PublicationStatus::NEW;
        $this->decision = '';
        $this->summary = '';

        $this->documents = new ArrayCollection();
        $this->departments = new ArrayCollection();
        $this->inquiries = new ArrayCollection();
        $this->decisionAttachments = new ArrayCollection();
    }

    public function getId(): Uuid
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

    public function getStatus(): PublicationStatus
    {
        return $this->status;
    }

    public function setStatus(PublicationStatus $status): self
    {
        if ($status === PublicationStatus::RETRACTED) {
            $this->publicationDate = null;
            $this->previewDate = null;
        }

        $this->status = $status;

        return $this;
    }

    public function getUploadStatus(): DossierUploadStatus
    {
        return new DossierUploadStatus($this);
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

    /**
     * @param Department[] $departments
     *
     * @return $this
     */
    public function setDepartments(array $departments): static
    {
        $this->departments->clear();
        $this->departments->add(...$departments);

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
     * @return Collection<array-key,Document>
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

    public function isAllowedState(PublicationStatus $newState): bool
    {
        $currentState = $this->getStatus();
        if (! isset($this->allowedStates[$currentState->value]) || ! in_array($newState, $this->allowedStates[$currentState->value])) {
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
     * @return Collection<array-key,Inquiry>
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

    public function getPreviewDate(): ?\DateTimeImmutable
    {
        return $this->previewDate;
    }

    public function setPreviewDate(?\DateTimeImmutable $previewDate): static
    {
        $this->previewDate = $previewDate;

        return $this;
    }

    public function isCompleted(): bool
    {
        return $this->completed;
    }

    public function setCompleted(bool $completed): static
    {
        $this->completed = $completed;

        return $this;
    }

    public function hasFuturePreviewDate(): bool
    {
        return $this->previewDate > new \DateTimeImmutable('today midnight');
    }

    public function hasFuturePublicationDate(): bool
    {
        return $this->publicationDate > new \DateTimeImmutable('today midnight');
    }

    public function getDecisionDate(): ?\DateTimeImmutable
    {
        return $this->decisionDate;
    }

    public function setDecisionDate(?\DateTimeImmutable $decisionDate): static
    {
        $this->decisionDate = $decisionDate;

        return $this;
    }

    public function getProcessRun(): ?InventoryProcessRun
    {
        return $this->processRun;
    }

    public function setProcessRun(InventoryProcessRun $run): self
    {
        if ($this->processRun?->isNotFinal()) {
            throw new \RuntimeException('Cannot overwrite a non-final InventoryProcessRun');
        }

        $this->processRun = $run;

        return $this;
    }

    public function isAvailableForBatchDownload(): bool
    {
        if (! $this->status->isPubliclyAvailable()) {
            return false;
        }

        if ($this->getUploadStatus()->getActualUploadCount() === 0) {
            return false;
        }

        return true;
    }

    public function getDownloadFilePrefix(): TranslatableMessage
    {
        return new TranslatableMessage(
            'filename-decision-{dossierNr}',
            [
                'dossierNr' => $this->dossierNr,
            ]
        );
    }

    public function getOrganisation(): Organisation
    {
        return $this->organisation;
    }

    public function setOrganisation(Organisation $organisation): static
    {
        $this->organisation = $organisation;

        return $this;
    }

    /**
     * @return Collection<array-key, DecisionAttachment>
     */
    public function getDecisionAttachments(): Collection
    {
        return $this->decisionAttachments;
    }

    public function addDecisionAttachment(DecisionAttachment $decisionAttachment): self
    {
        if (! $this->decisionAttachments->contains($decisionAttachment)) {
            $this->decisionAttachments->add($decisionAttachment);
        }

        return $this;
    }

    public function removeDecisionAttachment(DecisionAttachment $decisionAttachment): self
    {
        $this->decisionAttachments->removeElement($decisionAttachment);

        return $this;
    }
}
