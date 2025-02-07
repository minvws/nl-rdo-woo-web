<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Entity;

use App\Domain\Publication\Attachment\EntityWithAttachments;
use App\Domain\Publication\Attachment\HasAttachments;
use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Type\DossierType;
use App\Domain\Publication\Dossier\Type\DossierTypeWithPreview;
use App\Domain\Publication\Dossier\Type\DossierValidationGroup;
use App\Domain\Publication\Dossier\Type\WooDecision\DecisionType;
use App\Domain\Publication\Dossier\Type\WooDecision\PublicationReason;
use App\Domain\Publication\Dossier\Type\WooDecision\Repository\WooDecisionRepository;
use App\Domain\Publication\EntityWithBatchDownload;
use App\Domain\Publication\MainDocument\EntityWithMainDocument;
use App\Domain\Publication\MainDocument\HasMainDocument;
use App\ValueObject\DossierUploadStatus;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @implements EntityWithMainDocument<WooDecisionMainDocument>
 * @implements EntityWithAttachments<WooDecisionAttachment>
 */
#[ORM\Entity(repositoryClass: WooDecisionRepository::class)]
class WooDecision extends AbstractDossier implements EntityWithBatchDownload, DossierTypeWithPreview, EntityWithAttachments, EntityWithMainDocument
{
    /** @use HasMainDocument<WooDecisionMainDocument> */
    use HasMainDocument;

    /** @use HasAttachments<WooDecisionAttachment> */
    use HasAttachments;

    /** @var Collection<array-key, Document> */
    #[ORM\ManyToMany(targetEntity: Document::class, mappedBy: 'dossiers', fetch: 'EXTRA_LAZY')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    #[ORM\OrderBy(['documentNr' => 'ASC'])]
    protected Collection $documents;

    #[ORM\Column(length: 255, nullable: true, enumType: PublicationReason::class)]
    #[Assert\NotBlank(groups: [DossierValidationGroup::DETAILS->value])]
    private ?PublicationReason $publicationReason = null;

    /** @var Collection<array-key,Inquiry> */
    #[ORM\ManyToMany(targetEntity: Inquiry::class, mappedBy: 'dossiers')]
    #[ORM\JoinTable(name: 'inquiry_dossier')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    protected Collection $inquiries;

    #[ORM\OneToOne(mappedBy: 'dossier', targetEntity: Inventory::class)]
    private ?Inventory $inventory = null;

    #[ORM\OneToOne(mappedBy: 'dossier', targetEntity: ProductionReport::class)]
    protected ?ProductionReport $productionReport = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $previewDate = null;

    #[ORM\OneToOne(mappedBy: 'dossier', targetEntity: ProductionReportProcessRun::class)]
    private ?ProductionReportProcessRun $processRun = null;

    #[ORM\Column(length: 255, nullable: true, enumType: DecisionType::class)]
    #[Assert\NotBlank(groups: [DossierValidationGroup::DECISION->value])]
    private ?DecisionType $decision = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    #[Assert\NotBlank(groups: [DossierValidationGroup::DECISION->value])]
    #[Assert\LessThanOrEqual(value: 'today', message: 'date_must_not_be_in_future', groups: [DossierValidationGroup::DECISION->value])]
    private ?\DateTimeImmutable $decisionDate = null;

    #[ORM\OneToOne(mappedBy: 'dossier', targetEntity: WooDecisionMainDocument::class)]
    #[Assert\NotBlank(groups: [DossierValidationGroup::DECISION->value])]
    #[Assert\Valid(groups: [DossierValidationGroup::DECISION->value])]
    private ?WooDecisionMainDocument $document;

    /** @var Collection<array-key,WooDecisionAttachment> */
    #[ORM\OneToMany(mappedBy: 'dossier', targetEntity: WooDecisionAttachment::class)]
    private Collection $attachments;

    public function __construct()
    {
        parent::__construct();

        $this->setPublicationReason(PublicationReason::getDefault());

        $this->documents = new ArrayCollection();
        $this->inquiries = new ArrayCollection();
        $this->attachments = new ArrayCollection();
        $this->document = null;
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

    public function getProductionReport(): ?ProductionReport
    {
        return $this->productionReport;
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

    public function getProcessRun(): ?ProductionReportProcessRun
    {
        return $this->processRun;
    }

    public function setProcessRun(ProductionReportProcessRun $run): self
    {
        if ($this->processRun?->isNotFinal()) {
            throw new \RuntimeException('Cannot overwrite a non-final InventoryProcessRun');
        }

        $this->processRun = $run;

        return $this;
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

    public function getBatchFileName(): string
    {
        return sprintf('%s-%s', $this->documentPrefix, $this->dossierNr);
    }

    public function getType(): DossierType
    {
        return DossierType::WOO_DECISION;
    }

    public function getAttachmentEntityClass(): string
    {
        return WooDecisionAttachment::class;
    }

    public function getUploadStatus(): DossierUploadStatus
    {
        return new DossierUploadStatus($this);
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

    public function removeInquiry(Inquiry $inquiry): static
    {
        if ($this->inquiries->removeElement($inquiry)) {
            $inquiry->removeDossier($this);
        }

        return $this;
    }

    public function addInquiry(Inquiry $inquiry): self
    {
        if (! $this->inquiries->contains($inquiry)) {
            $this->inquiries->add($inquiry);
            $inquiry->addDossier($this);
        }

        return $this;
    }

    public function setProductionReport(?ProductionReport $productionReport): static
    {
        // set the owning side of the relation if necessary
        if ($productionReport !== null && $productionReport->getDossier() !== $this) {
            $productionReport->setDossier($this);
        }

        $this->productionReport = $productionReport;

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

    public function getMainDocumentEntityClass(): string
    {
        return WooDecisionMainDocument::class;
    }
}
