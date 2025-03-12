<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\OtherPublication;

use App\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use App\Domain\Publication\Attachment\Enum\AttachmentType;
use App\Domain\Publication\MainDocument\AbstractMainDocument;
use Doctrine\ORM\Mapping as ORM;

/**
 * @extends AbstractMainDocument<OtherPublication>
 */
#[ORM\Entity(repositoryClass: OtherPublicationMainDocumentRepository::class)]
#[ORM\HasLifecycleCallbacks]
class OtherPublicationMainDocument extends AbstractMainDocument
{
    public function __construct(
        OtherPublication $dossier,
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
        return AttachmentType::getCasesWithout(
            AttachmentType::DESIGNATION_DECISION,
            AttachmentType::APPOINTMENT_DECISION,
            AttachmentType::CONCESSION,
            AttachmentType::RECOGNITION_DECISION,
            AttachmentType::CONSENT_DECISION,
            AttachmentType::EXEMPTION_DECISION,
            AttachmentType::SUBSIDY_DECISION,
            AttachmentType::PERMIT,
        );
    }
}
