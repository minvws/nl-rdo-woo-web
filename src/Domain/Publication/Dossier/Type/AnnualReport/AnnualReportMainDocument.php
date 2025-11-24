<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\AnnualReport;

use Doctrine\ORM\Mapping as ORM;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;

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
