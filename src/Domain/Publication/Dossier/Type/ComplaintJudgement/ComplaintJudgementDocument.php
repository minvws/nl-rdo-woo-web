<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\ComplaintJudgement;

use App\Domain\Publication\Attachment\AttachmentLanguage;
use App\Domain\Publication\Attachment\AttachmentType;
use App\Domain\Publication\MainDocument\AbstractMainDocument;
use App\Service\Uploader\UploadGroupId;
use Doctrine\ORM\Mapping as ORM;

/**
 * @extends AbstractMainDocument<ComplaintJudgement>
 */
#[ORM\Entity(repositoryClass: ComplaintJudgementDocumentRepository::class)]
#[ORM\HasLifecycleCallbacks]
class ComplaintJudgementDocument extends AbstractMainDocument
{
    public function __construct(
        ComplaintJudgement $dossier,
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
        return UploadGroupId::COMPLAINT_JUDGEMENT_DOCUMENTS;
    }

    /**
     * @return list<AttachmentType>
     */
    public static function getAllowedTypes(): array
    {
        return [
            AttachmentType::COMPLAINT_JUDGEMENT,
        ];
    }
}
