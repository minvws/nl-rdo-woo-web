<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\AnnualReport;

use App\Domain\Publication\Attachment\AttachmentLanguage;
use App\Domain\Publication\Attachment\AttachmentType;
use App\Domain\Publication\MainDocument\AbstractMainDocument;
use App\Service\Uploader\UploadGroupId;
use Doctrine\ORM\Mapping as ORM;
use Webmozart\Assert\Assert;

/**
 * @property AttachmentType::ANNUAL_REPORT|AttachmentType::ANNUAL_PLAN $type
 *
 * @extends AbstractMainDocument<AnnualReport>
 */
#[ORM\Entity(repositoryClass: AnnualReportDocumentRepository::class)]
#[ORM\HasLifecycleCallbacks]
class AnnualReportDocument extends AbstractMainDocument
{
    public function __construct(
        AnnualReport $dossier,
        \DateTimeImmutable $formalDate,
        AttachmentType $type,
        AttachmentLanguage $language,
    ) {
        parent::__construct();

        Assert::oneOf($type, self::getAllowedTypes(), sprintf('Not allowed attachment type given: %s', $type->name));

        $this->dossier = $dossier;
        $this->formalDate = $formalDate;
        $this->type = $type;
        $this->language = $language;
        $this->fileInfo->setPaginatable(true);
    }

    public static function getUploadGroupId(): UploadGroupId
    {
        return UploadGroupId::ANNUAL_REPORT_DOCUMENTS;
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
