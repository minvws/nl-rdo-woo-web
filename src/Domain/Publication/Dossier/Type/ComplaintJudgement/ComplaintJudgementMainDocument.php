<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\ComplaintJudgement;

use Doctrine\ORM\Mapping as ORM;
use Override;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;
use Shared\ValueObject\PlainDate;

/**
 * @extends AbstractMainDocument<ComplaintJudgement>
 */
#[ORM\Entity(repositoryClass: ComplaintJudgementMainDocumentRepository::class)]
#[ORM\HasLifecycleCallbacks]
class ComplaintJudgementMainDocument extends AbstractMainDocument
{
    public function __construct(
        ComplaintJudgement $dossier,
        PlainDate $formalDate,
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
    #[Override]
    public static function getAllowedTypes(): array
    {
        return [
            AttachmentType::COMPLAINT_JUDGEMENT,
        ];
    }
}
