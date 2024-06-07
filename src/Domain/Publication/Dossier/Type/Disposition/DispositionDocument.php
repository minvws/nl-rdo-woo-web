<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\Disposition;

use App\Domain\Publication\Attachment\AttachmentLanguage;
use App\Domain\Publication\Attachment\AttachmentType;
use App\Domain\Publication\MainDocument\AbstractMainDocument;
use App\Service\Uploader\UploadGroupId;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DispositionDocumentRepository::class)]
#[ORM\HasLifecycleCallbacks]
class DispositionDocument extends AbstractMainDocument
{
    #[ORM\OneToOne(inversedBy: 'document', targetEntity: Disposition::class)]
    #[ORM\JoinColumn(name: 'dossier_id', referencedColumnName: 'id', nullable: false, onDelete: 'cascade')]
    private Disposition $dossier;

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
    }

    public function getDossier(): Disposition
    {
        return $this->dossier;
    }

    public function getUploadGroupId(): UploadGroupId
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
            AttachmentType::DECISION_ON_REQUEST_ART3_WOB,
            AttachmentType::DECISION_ON_REQUEST_ART4_1_WOO,
            AttachmentType::CONCESSION,
            AttachmentType::RECOGNITION_DECISION,
            AttachmentType::CONSENT_DECISION,
            AttachmentType::EXEMPTION_DECISION,
            AttachmentType::SUBSIDY_DECISION,
            AttachmentType::PERMIT,
        ];
    }
}
