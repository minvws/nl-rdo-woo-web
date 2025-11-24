<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\InvestigationReport;

use Doctrine\ORM\Mapping as ORM;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;

/**
 * @extends AbstractMainDocument<InvestigationReport>
 */
#[ORM\Entity(repositoryClass: InvestigationReportMainDocumentRepository::class)]
#[ORM\HasLifecycleCallbacks]
class InvestigationReportMainDocument extends AbstractMainDocument
{
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
