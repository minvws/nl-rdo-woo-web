<?php

declare(strict_types=1);

namespace App\Entity;

use App\Domain\Publication\Attachment\EntityWithAttachments;
use App\Domain\Publication\Attachment\HasAttachments;
use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Type\DossierTypeWithPreview;
use App\Domain\Publication\Dossier\Type\DossierValidationGroup;
use App\Domain\Publication\Dossier\Type\WooDecision\DecisionType;
use App\Domain\Publication\Dossier\Type\WooDecision\PublicationReason;
use App\Repository\DossierRepository;
use App\ValueObject\DossierUploadStatus;
use App\ValueObject\TranslatableMessage;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @deprecated use WooDecision instead
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @implements EntityWithAttachments<DecisionAttachment>
 */
#[ORM\Entity(repositoryClass: DossierRepository::class)]
abstract class Dossier extends AbstractDossier implements EntityWithBatchDownload, DossierTypeWithPreview, EntityWithAttachments
{
    /** @use HasAttachments<DecisionAttachment> */
    use HasAttachments;

    /** @var Collection<array-key, Document> */
    #[ORM\ManyToMany(targetEntity: Document::class, mappedBy: 'dossiers', fetch: 'EXTRA_LAZY')]
    #[ORM\OrderBy(['documentNr' => 'ASC'])]
    private Collection $documents;

    #[ORM\Column(length: 255, nullable: true, enumType: PublicationReason::class)]
    #[Assert\NotBlank(groups: [DossierValidationGroup::DETAILS->value])]
    private ?PublicationReason $publicationReason = null;

    /** @var Collection<array-key,Inquiry> */
    #[ORM\ManyToMany(targetEntity: Inquiry::class, mappedBy: 'dossiers')]
    #[ORM\JoinTable(name: 'inquiry_dossier')]
    private Collection $inquiries;

    #[ORM\OneToOne(mappedBy: 'dossier', targetEntity: Inventory::class)]
    private ?Inventory $inventory = null;

    #[ORM\OneToOne(mappedBy: 'dossier', targetEntity: RawInventory::class)]
    private ?RawInventory $rawInventory = null;

    /**
     * @var string[]|null
     */
    #[ORM\Column(nullable: true)]
    private ?array $defaultSubjects = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $previewDate = null;

    #[ORM\OneToOne(mappedBy: 'dossier', targetEntity: InventoryProcessRun::class)]
    private ?InventoryProcessRun $processRun = null;

    #[ORM\Column(length: 255, nullable: true, enumType: DecisionType::class)]
    #[Assert\NotBlank(groups: [DossierValidationGroup::DECISION->value])]
    private ?DecisionType $decision = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    #[Assert\NotBlank(groups: [DossierValidationGroup::DECISION->value])]
    #[Assert\LessThanOrEqual(value: 'today', message: 'date_must_not_be_in_future', groups: [DossierValidationGroup::DECISION->value])]
    private ?\DateTimeImmutable $decisionDate = null;

    #[ORM\OneToOne(mappedBy: 'dossier', targetEntity: DecisionDocument::class)]
    #[Assert\NotBlank(groups: [DossierValidationGroup::DECISION->value])]
    private ?DecisionDocument $decisionDocument = null;

    /** @var Collection<array-key,DecisionAttachment> */
    #[ORM\OneToMany(mappedBy: 'dossier', targetEntity: DecisionAttachment::class)]
    private Collection $attachments;

    public function __construct()
    {
        parent::__construct();

        $this->documents = new ArrayCollection();
        $this->inquiries = new ArrayCollection();
        $this->attachments = new ArrayCollection();
    }

    public function getUploadStatus(): DossierUploadStatus
    {
        return new DossierUploadStatus($this);
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

    public function getPublicationReason(): ?PublicationReason
    {
        return $this->publicationReason;
    }

    public function setPublicationReason(PublicationReason $publicationReason): static
    {
        $this->publicationReason = $publicationReason;

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
        return $this->getDecision() !== DecisionType::NOTHING_FOUND
            && $this->getDecision() !== DecisionType::NOT_PUBLIC;
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

    public function hasFuturePreviewDate(): bool
    {
        return $this->previewDate > new \DateTimeImmutable('today midnight');
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

    public function getDecision(): ?DecisionType
    {
        return $this->decision;
    }

    public function setDecision(DecisionType $decision): static
    {
        $this->decision = $decision;

        return $this;
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

    public function getDecisionDocument(): ?DecisionDocument
    {
        return $this->decisionDocument;
    }

    public function setDecisionDocument(?DecisionDocument $decisionDocument): self
    {
        $this->decisionDocument = $decisionDocument;

        return $this;
    }

    public function getDownloadFilePrefix(): TranslatableMessage
    {
        return new TranslatableMessage(
            'admin.dossiers.decision.number',
            [
                'dossierNr' => $this->dossierNr,
            ]
        );
    }
}
