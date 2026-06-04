<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\AnnualReport;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Override;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\Attachment\Entity\EntityWithAttachments;
use Shared\Domain\Publication\Attachment\Entity\HasAttachments;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\DossierType;
use Shared\Domain\Publication\Dossier\Type\DossierValidationGroup;
use Shared\Domain\Publication\Dossier\Validator\NoIncompleteAttachments;
use Shared\Domain\Publication\MainDocument\EntityWithMainDocument;
use Shared\Domain\Publication\MainDocument\HasMainDocument;
use Shared\ValueObject\PlainDate;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @implements EntityWithAttachments<AnnualReportAttachment>
 * @implements EntityWithMainDocument<AnnualReportMainDocument>
 */
#[NoIncompleteAttachments(groups: [
    DossierValidationGroup::WORKFLOW_SCHEDULE_PUBLISH->value,
    DossierValidationGroup::WORKFLOW_PUBLISH->value,
])]
#[ORM\Entity(repositoryClass: AnnualReportRepository::class)]
class AnnualReport extends AbstractDossier implements EntityWithAttachments, EntityWithMainDocument
{
    /** @use HasAttachments<AnnualReportAttachment> */
    use HasAttachments;

    /** @use HasMainDocument<AnnualReportMainDocument> */
    use HasMainDocument;

    #[ORM\OneToOne(mappedBy: 'dossier', targetEntity: AnnualReportMainDocument::class, cascade: ['persist', 'remove'])]
    #[Assert\NotBlank(groups: [
        DossierValidationGroup::DECISION->value,
        DossierValidationGroup::WORKFLOW_SCHEDULE_PUBLISH->value,
        DossierValidationGroup::WORKFLOW_PUBLISH->value,
    ])]
    #[Assert\Valid(groups: [
        DossierValidationGroup::DECISION->value,
        DossierValidationGroup::WORKFLOW_SCHEDULE_PUBLISH->value,
        DossierValidationGroup::WORKFLOW_PUBLISH->value,
    ])]
    private ?AnnualReportMainDocument $document;

    /** @var Collection<array-key,AnnualReportAttachment> */
    #[ORM\OneToMany(mappedBy: 'dossier', targetEntity: AnnualReportAttachment::class, cascade: ['persist'])]
    #[Assert\Count(max: AbstractAttachment::MAX_ATTACHMENTS_PER_DOSSIER)]
    private Collection $attachments;

    #[Assert\NotNull(
        message: 'annual_report_year_mandatory',
        groups: [
            DossierValidationGroup::DETAILS->value,
            DossierValidationGroup::WORKFLOW_SCHEDULE_PUBLISH->value,
            DossierValidationGroup::WORKFLOW_PUBLISH->value,
        ],
    )]
    protected ?PlainDate $dateFrom = null;

    #[Assert\Length(min: 1, max: 1000, groups: [
        DossierValidationGroup::DECISION->value,
        DossierValidationGroup::CONTENT->value,
        DossierValidationGroup::WORKFLOW_SCHEDULE_PUBLISH->value,
        DossierValidationGroup::WORKFLOW_PUBLISH->value,
    ])]
    protected string $summary = '';

    public function __construct()
    {
        parent::__construct();

        $this->attachments = new ArrayCollection();
        $this->document = null;
    }

    #[Override]
    public function setDateFrom(?PlainDate $dateFrom): static
    {
        if ($dateFrom === null) {
            $dateFrom = PlainDate::today();
        }

        $this->dateFrom = $dateFrom->firstOfYear();
        $this->dateTo = $dateFrom->lastOfYear();

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
