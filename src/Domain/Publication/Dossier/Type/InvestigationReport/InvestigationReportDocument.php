<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\InvestigationReport;

use App\Domain\Publication\Attachment\AttachmentLanguage;
use App\Domain\Publication\Attachment\AttachmentType;
use App\Domain\Publication\MainDocument\AbstractMainDocument;
use App\Service\Uploader\UploadGroupId;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InvestigationReportDocumentRepository::class)]
#[ORM\HasLifecycleCallbacks]
class InvestigationReportDocument extends AbstractMainDocument
{
    #[ORM\OneToOne(inversedBy: 'document', targetEntity: InvestigationReport::class)]
    #[ORM\JoinColumn(name: 'dossier_id', referencedColumnName: 'id', nullable: false, onDelete: 'cascade')]
    private InvestigationReport $dossier;

    public function __construct(
        InvestigationReport $dossier,
        \DateTimeImmutable $formalDate,
        AttachmentType $type,
        AttachmentLanguage $language,
    ) {
        parent::__construct();

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

    public static function getUploadGroupId(): UploadGroupId
    {
        return UploadGroupId::INVESTIGATION_REPORT_DOCUMENTS;
    }

    /**
     * @return list<AttachmentType>
     */
    public static function getAllowedTypes(): array
    {
        return [
            AttachmentType::OFFICIAL_MESSAGE,
            AttachmentType::EVALUATION_REPORT,
            AttachmentType::INSPECTION_REPORT,
            AttachmentType::RESEARCH_REPORT,
            AttachmentType::ACCOUNTABILITY_REPORT,
            AttachmentType::PROGRESS_REPORT,
        ];
    }
}
