<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\AnnualReport;

use App\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use App\Domain\Publication\Attachment\Enum\AttachmentType;
use App\Domain\Publication\MainDocument\AbstractMainDocument;
use Doctrine\ORM\Mapping as ORM;

/**
 * @extends AbstractMainDocument<AnnualReport>
 */
#[ORM\Entity(repositoryClass: AnnualReportMainDocumentRepository::class)]
#[ORM\HasLifecycleCallbacks]
class AnnualReportMainDocument extends AbstractMainDocument
{
    public function __construct(
        AnnualReport $dossier,
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
     * @return list<AttachmentType::ANNUAL_REPORT|AttachmentType::ANNUAL_PLAN>
     */
    public static function getAllowedTypes(): array
    {
        return [
            AttachmentType::ANNUAL_REPORT,
            AttachmentType::ANNUAL_PLAN,
        ];
    }
}
