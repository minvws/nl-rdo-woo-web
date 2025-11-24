<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\ComplaintJudgement;

use Doctrine\ORM\Mapping as ORM;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;

/**
 * @extends AbstractMainDocument<ComplaintJudgement>
 */
#[ORM\Entity(repositoryClass: ComplaintJudgementMainDocumentRepository::class)]
#[ORM\HasLifecycleCallbacks]
class ComplaintJudgementMainDocument extends AbstractMainDocument
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
