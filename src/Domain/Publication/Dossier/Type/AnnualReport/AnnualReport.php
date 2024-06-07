<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\AnnualReport;

use App\Domain\Publication\Attachment\EntityWithAttachments;
use App\Domain\Publication\Attachment\HasAttachments;
use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Type\DossierType;
use App\Domain\Publication\Dossier\Type\DossierValidationGroup;
use App\Domain\Publication\MainDocument\EntityWithMainDocument;
use App\Domain\Publication\MainDocument\HasMainDocument;
use Carbon\CarbonImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @implements EntityWithAttachments<AnnualReportAttachment>
 * @implements EntityWithMainDocument<AnnualReportDocument>
 */
#[ORM\Entity(repositoryClass: AnnualReportRepository::class)]
class AnnualReport extends AbstractDossier implements EntityWithAttachments, EntityWithMainDocument
{
    /** @use HasAttachments<AnnualReportAttachment> */
    use HasAttachments;

    /** @use HasMainDocument<AnnualReportDocument> */
    use HasMainDocument;

    #[ORM\OneToOne(mappedBy: 'dossier', targetEntity: AnnualReportDocument::class)]
    #[Assert\NotBlank(groups: [DossierValidationGroup::CONTENT->value])]
    #[Assert\Valid(groups: [DossierValidationGroup::CONTENT->value])]
    private ?AnnualReportDocument $document;

    /** @var Collection<array-key,AnnualReportAttachment> */
    #[ORM\OneToMany(mappedBy: 'dossier', targetEntity: AnnualReportAttachment::class, orphanRemoval: true)]
    private Collection $attachments;

    public function __construct()
    {
        parent::__construct();

        $this->attachments = new ArrayCollection();
        $this->document = null;
    }

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
        return AnnualReportDocument::class;
    }
}
