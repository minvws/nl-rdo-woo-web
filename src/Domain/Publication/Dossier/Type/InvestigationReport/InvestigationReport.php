<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\InvestigationReport;

use App\Domain\Publication\Attachment\EntityWithAttachments;
use App\Domain\Publication\Attachment\HasAttachments;
use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Type\DossierType;
use App\Domain\Publication\Dossier\Type\DossierValidationGroup;
use App\Domain\Publication\MainDocument\EntityWithMainDocument;
use App\Domain\Publication\MainDocument\HasMainDocument;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @implements EntityWithAttachments<InvestigationReportAttachment>
 * @implements EntityWithMainDocument<InvestigationReportDocument>
 */
#[ORM\Entity(repositoryClass: InvestigationReportRepository::class)]
class InvestigationReport extends AbstractDossier implements EntityWithAttachments, EntityWithMainDocument
{
    /** @use HasAttachments<InvestigationReportAttachment> */
    use HasAttachments;

    /** @use HasMainDocument<InvestigationReportDocument> */
    use HasMainDocument;

    #[ORM\OneToOne(mappedBy: 'dossier', targetEntity: InvestigationReportDocument::class)]
    #[Assert\NotBlank(groups: [DossierValidationGroup::CONTENT->value])]
    #[Assert\Valid(groups: [DossierValidationGroup::CONTENT->value])]
    private ?InvestigationReportDocument $document;

    /** @var Collection<array-key,InvestigationReportAttachment> */
    #[ORM\OneToMany(mappedBy: 'dossier', targetEntity: InvestigationReportAttachment::class)]
    private Collection $attachments;

    public function __construct()
    {
        parent::__construct();

        $this->attachments = new ArrayCollection();
        $this->document = null;
    }

    public function setDateFrom(?\DateTimeImmutable $dateFrom): static
    {
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateFrom;

        return $this;
    }

    public function getType(): DossierType
    {
        return DossierType::INVESTIGATION_REPORT;
    }

    public function getAttachmentEntityClass(): string
    {
        return InvestigationReportAttachment::class;
    }

    public function getMainDocumentEntityClass(): string
    {
        return InvestigationReportDocument::class;
    }
}
