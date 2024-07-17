<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\AnnualReport;

use App\Domain\Publication\Attachment\AbstractAttachment;
use App\Domain\Publication\Attachment\AttachmentLanguage;
use App\Domain\Publication\Attachment\AttachmentType;
use App\Domain\Publication\Dossier\AbstractDossier;
use App\Service\Uploader\UploadGroupId;
use Doctrine\ORM\Mapping as ORM;
use Webmozart\Assert\Assert;

#[ORM\Entity(repositoryClass: AnnualReportAttachmentRepository::class)]
class AnnualReportAttachment extends AbstractAttachment
{
    public function __construct(
        AbstractDossier $dossier,
        \DateTimeImmutable $formalDate,
        AttachmentType $type,
        AttachmentLanguage $language,
    ) {
        parent::__construct();

        Assert::oneOf($type, self::getAllowedTypes(), sprintf('Not allowed attachment type given: %s', $type->name));
        Assert::isInstanceOf($dossier, AnnualReport::class);

        $this->dossier = $dossier;
        $this->formalDate = $formalDate;
        $this->type = $type;
        $this->language = $language;
        $this->fileInfo->setPaginatable(true);
    }

    public static function getUploadGroupId(): UploadGroupId
    {
        return UploadGroupId::ANNUAL_REPORT_ATTACHMENTS;
    }

    /**
     * @return list<AttachmentType>
     */
    public static function getAllowedTypes(): array
    {
        $filtered = array_filter(AttachmentType::cases(), fn (AttachmentType $value): bool => ! in_array($value, [
            AttachmentType::ANNUAL_REPORT,
            AttachmentType::ANNUAL_PLAN,
        ], true));

        return array_values($filtered);
    }
}
