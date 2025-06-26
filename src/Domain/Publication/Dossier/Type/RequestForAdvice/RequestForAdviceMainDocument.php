<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\RequestForAdvice;

use App\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use App\Domain\Publication\Attachment\Enum\AttachmentType;
use App\Domain\Publication\MainDocument\AbstractMainDocument;
use Doctrine\ORM\Mapping as ORM;

/**
 * @extends AbstractMainDocument<RequestForAdvice>
 */
#[ORM\Entity(repositoryClass: RequestForAdviceMainDocumentRepository::class)]
#[ORM\HasLifecycleCallbacks]
class RequestForAdviceMainDocument extends AbstractMainDocument
{
    public function __construct(
        RequestForAdvice $dossier,
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
