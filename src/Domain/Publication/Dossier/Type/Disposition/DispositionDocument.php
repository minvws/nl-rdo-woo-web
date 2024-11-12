<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\Disposition;

use App\Domain\Publication\Attachment\AttachmentLanguage;
use App\Domain\Publication\Attachment\AttachmentType;
use App\Domain\Publication\MainDocument\AbstractMainDocument;
use App\Service\Uploader\UploadGroupId;
use Doctrine\ORM\Mapping as ORM;

/**
 * @extends AbstractMainDocument<Disposition>
 */
#[ORM\Entity(repositoryClass: DispositionDocumentRepository::class)]
#[ORM\HasLifecycleCallbacks]
class DispositionDocument extends AbstractMainDocument
{
    public function __construct(
        Disposition $dossier,
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

    public static function getUploadGroupId(): UploadGroupId
    {
        return UploadGroupId::DISPOSITION_DOCUMENTS;
    }

    /**
     * @return list<AttachmentType>
     */
    public static function getAllowedTypes(): array
    {
        return [
            AttachmentType::DECISION_TO_IMPOSE_A_FINE,
            AttachmentType::DECISION_TO_IMPOSE_AN_ORDER_UNDER_ADMINISTRATIVE_ENFORCEMENT,
            AttachmentType::DECISION_TO_IMPOSE_AN_ORDER_SUBJECT_TO_PENALTY,
            AttachmentType::DESIGNATION_DECISION,
            AttachmentType::APPOINTMENT_DECISION,
            AttachmentType::CONCESSION,
            AttachmentType::RECOGNITION_DECISION,
            AttachmentType::CONSENT_DECISION,
            AttachmentType::EXEMPTION_DECISION,
            AttachmentType::SUBSIDY_DECISION,
            AttachmentType::PERMIT,
        ];
    }
}
