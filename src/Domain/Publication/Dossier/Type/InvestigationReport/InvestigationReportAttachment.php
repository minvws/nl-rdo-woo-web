<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\InvestigationReport;

use App\Domain\Publication\Attachment\AbstractAttachment;
use App\Domain\Publication\Attachment\AttachmentLanguage;
use App\Domain\Publication\Attachment\AttachmentType;
use App\Domain\Publication\Dossier\AbstractDossier;
use App\Service\Uploader\UploadGroupId;
use Doctrine\ORM\Mapping as ORM;
use Webmozart\Assert\Assert;

#[ORM\Entity(repositoryClass: InvestigationReportAttachmentRepository::class)]
#[ORM\HasLifecycleCallbacks]
class InvestigationReportAttachment extends AbstractAttachment
{
    #[ORM\ManyToOne(targetEntity: InvestigationReport::class, inversedBy: 'attachments')]
    #[ORM\JoinColumn(name: 'dossier_id', referencedColumnName: 'id', nullable: false, onDelete: 'cascade')]
    private InvestigationReport $dossier;

    public function __construct(
        AbstractDossier $dossier,
        \DateTimeImmutable $formalDate,
        AttachmentType $type,
        AttachmentLanguage $language,
    ) {
        parent::__construct();

        Assert::isInstanceOf($dossier, InvestigationReport::class);

        $this->dossier = $dossier;
        $this->formalDate = $formalDate;
        $this->type = $type;
        $this->language = $language;
        $this->fileInfo->setPaginatable(true);
    }

    public function getDossier(): InvestigationReport
    {
        return $this->dossier;
    }

    public function getUploadGroupId(): UploadGroupId
    {
        return UploadGroupId::INVESTIGATION_REPORT_ATTACHMENTS;
    }

    /**
     * @return list<AttachmentType>
     */
    public static function getAllowedTypes(): array
    {
        $filtered = array_filter(
            AttachmentType::cases(),
            static fn (AttachmentType $value): bool => ! in_array($value, InvestigationReportDocument::getAllowedTypes(), true),
        );

        return array_values($filtered);
    }
}
