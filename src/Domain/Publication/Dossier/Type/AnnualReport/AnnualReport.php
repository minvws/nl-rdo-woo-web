<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\AnnualReport;

use Carbon\CarbonImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\Attachment\Entity\EntityWithAttachments;
use Shared\Domain\Publication\Attachment\Entity\HasAttachments;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\DossierType;
use Shared\Domain\Publication\Dossier\Type\DossierValidationGroup;
use Shared\Domain\Publication\MainDocument\EntityWithMainDocument;
use Shared\Domain\Publication\MainDocument\HasMainDocument;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @implements EntityWithAttachments<AnnualReportAttachment>
 * @implements EntityWithMainDocument<AnnualReportMainDocument>
 */
#[ORM\Entity(repositoryClass: AnnualReportRepository::class)]
class AnnualReport extends AbstractDossier implements EntityWithAttachments, EntityWithMainDocument
{
    /** @use HasAttachments<AnnualReportAttachment> */
    use HasAttachments;

    /** @use HasMainDocument<AnnualReportMainDocument> */
    use HasMainDocument;

    #[ORM\OneToOne(mappedBy: 'dossier', targetEntity: AnnualReportMainDocument::class, cascade: ['remove'])]
    #[Assert\NotBlank(groups: [DossierValidationGroup::CONTENT->value])]
    #[Assert\Valid(groups: [DossierValidationGroup::CONTENT->value])]
    private ?AnnualReportMainDocument $document;

    /** @var Collection<array-key,AnnualReportAttachment> */
    #[ORM\OneToMany(mappedBy: 'dossier', targetEntity: AnnualReportAttachment::class)]
    #[Assert\Count(max: AbstractAttachment::MAX_ATTACHMENTS_PER_DOSSIER)]
    private Collection $attachments;

    public function __construct()
    {
        parent::__construct();

        $this->attachments = new ArrayCollection();
        $this->document = null;
    }

    #[\Override]
    public function setDateFrom(?\DateTimeImmutable $dateFrom): static
    {
        $carbonDate = new CarbonImmutable($dateFrom);

        $this->dateFrom = $carbonDate->firstOfYear();
        $this->dateTo = $carbonDate->lastOfYear();

        return $this;
    }

    public function getType(): DossierType
    {
        return DossierType::ANNUAL_REPORT;
    }

    public function getAttachmentEntityClass(): string
    {
        return AnnualReportAttachment::class;
    }

    public function getMainDocumentEntityClass(): string
    {
        return AnnualReportMainDocument::class;
    }
}
