<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\Advice;

use Doctrine\ORM\Mapping as ORM;
use Override;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;
use Shared\ValueObject\PlainDate;

/**
 * @extends AbstractMainDocument<Advice>
 */
#[ORM\Entity(repositoryClass: AdviceMainDocumentRepository::class)]
#[ORM\HasLifecycleCallbacks]
class AdviceMainDocument extends AbstractMainDocument
{
    public function __construct(
        Advice $dossier,
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
            AttachmentType::ADVICE,
        ];
    }
}
