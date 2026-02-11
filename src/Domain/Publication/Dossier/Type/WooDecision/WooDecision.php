<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use RuntimeException;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\Attachment\Entity\EntityWithAttachments;
use Shared\Domain\Publication\Attachment\Entity\HasAttachments;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\DossierType;
use Shared\Domain\Publication\Dossier\Type\DossierTypeWithPreview;
use Shared\Domain\Publication\Dossier\Type\DossierValidationGroup;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Attachment\WooDecisionAttachment;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Decision\DecisionType;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inventory\Inventory;
use Shared\Domain\Publication\Dossier\Type\WooDecision\MainDocument\WooDecisionMainDocument;
use Shared\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReport;
use Shared\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReportProcessRun;
use Shared\Domain\Publication\MainDocument\EntityWithMainDocument;
use Shared\Domain\Publication\MainDocument\HasMainDocument;
use Symfony\Component\Validator\Constraints as Assert;

use function in_array;

/**
 * @implements EntityWithMainDocument<WooDecisionMainDocument>
 * @implements EntityWithAttachments<WooDecisionAttachment>
 */
#[ORM\Entity(repositoryClass: WooDecisionRepository::class)]
class WooDecision extends AbstractDossier implements DossierTypeWithPreview, EntityWithAttachments, EntityWithMainDocument
{
    /** @use HasMainDocument<WooDecisionMainDocument> */
    use HasMainDocument;

    /** @use HasAttachments<WooDecisionAttachment> */
    use HasAttachments;

    /** @var Collection<array-key, Document> */
    #[ORM\ManyToMany(targetEntity: Document::class, mappedBy: 'dossiers', fetch: 'EXTRA_LAZY', cascade: ['persist'])]
    #[ORM\OrderBy(['documentNr' => 'ASC'])]
    protected Collection $documents;

    #[ORM\Column(length: 255, nullable: true, enumType: PublicationReason::class)]
    #[Assert\NotBlank(groups: [DossierValidationGroup::DETAILS->value])]
    private ?PublicationReason $publicationReason = null;

    /** @var Collection<array-key,Inquiry> */
    #[ORM\ManyToMany(targetEntity: Inquiry::class, mappedBy: 'dossiers')]
    protected Collection $inquiries;

    #[ORM\OneToOne(mappedBy: 'dossier', targetEntity: Inventory::class, cascade: ['remove'])]
    private ?Inventory $inventory = null;

    #[ORM\OneToOne(mappedBy: 'dossier', targetEntity: ProductionReport::class, cascade: ['remove'])]
    protected ?ProductionReport $productionReport = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $previewDate = null;

    #[ORM\OneToOne(mappedBy: 'dossier', targetEntity: ProductionReportProcessRun::class, cascade: ['remove'])]
    private ?ProductionReportProcessRun $processRun = null;

    #[ORM\Column(length: 255, nullable: true, enumType: DecisionType::class)]
    #[Assert\NotBlank(groups: [DossierValidationGroup::DECISION->value])]
    private ?DecisionType $decision = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    #[Assert\NotBlank(groups: [DossierValidationGroup::DECISION->value])]
    #[Assert\LessThanOrEqual(value: 'today', message: 'date_must_not_be_in_future', groups: [DossierValidationGroup::DECISION->value])]
    private ?DateTimeImmutable $decisionDate = null;

    #[ORM\OneToOne(mappedBy: 'dossier', targetEntity: WooDecisionMainDocument::class, cascade: ['remove', 'persist'])]
    #[Assert\NotBlank(groups: [DossierValidationGroup::DECISION->value])]
    #[Assert\Valid(groups: [DossierValidationGroup::DECISION->value])]
    private ?WooDecisionMainDocument $document;

    /** @var Collection<array-key,WooDecisionAttachment> */
    #[ORM\OneToMany(mappedBy: 'dossier', targetEntity: WooDecisionAttachment::class, cascade: ['persist'], orphanRemoval: true)]
    #[Assert\Count(max: AbstractAttachment::MAX_ATTACHMENTS_PER_DOSSIER)]
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

    public function isInventoryRequired(): bool
    {
        return $this->isDecisionOneOfTypes([DecisionType::PUBLIC, DecisionType::PARTIAL_PUBLIC]);
    }

    public function isInventoryOptional(): bool
    {
        return $this->isDecisionOneOfTypes([DecisionType::ALREADY_PUBLIC, DecisionType::NOT_PUBLIC]);
    }

    /**
     * @param DecisionType[] $decisionTypes
     */
    private function isDecisionOneOfTypes(array $decisionTypes): bool
    {
        return in_array($this->getDecision(), $decisionTypes, true);
    }

    public function canProvideInventory(): bool
    {
        return $this->isInventoryRequired() || $this->isInventoryOptional();
    }

    public function hasProductionReport(): bool
    {
        return $this->getProductionReport()?->getFileInfo()->isUploaded() ?? false;
    }

    public function hasAllExpectedUploads(): bool
    {
        return $this->hasProductionReport() && $this->getUploadStatus()->isComplete();
    }

    public function getPreviewDate(): ?DateTimeImmutable
    {
        return $this->previewDate;
    }

    public function setPreviewDate(?DateTimeImmutable $previewDate): static
    {
        $this->previewDate = $previewDate;

        return $this;
    }

    public function hasFuturePreviewDate(): bool
    {
        return $this->previewDate > new DateTimeImmutable('today midnight');
    }

    public function getProcessRun(): ?ProductionReportProcessRun
    {
        return $this->processRun;
    }

    public function setProcessRun(ProductionReportProcessRun $run): self
    {
        if ($this->processRun?->isNotFinal()) {
            throw new RuntimeException('Cannot overwrite a non-final InventoryProcessRun');
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

    public function getDecisionDate(): ?DateTimeImmutable
    {
        return $this->decisionDate;
    }

    public function setDecisionDate(?DateTimeImmutable $decisionDate): static
    {
        $this->decisionDate = $decisionDate;

        return $this;
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

    public function getMainDocumentEntityClass(): string
    {
        return WooDecisionMainDocument::class;
    }

    public function hasWithdrawnOrSuspendedDocuments(): bool
    {
        return $this->getDocuments()->exists(
            static function (int $key, Document $document) {
                unset($key);

                return $document->isWithdrawn() || $document->isSuspended();
            }
        );
    }
}
