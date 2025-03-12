<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\Advice;

use App\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use App\Domain\Publication\Attachment\Enum\AttachmentType;
use App\Domain\Publication\MainDocument\AbstractMainDocument;
use Doctrine\ORM\Mapping as ORM;

/**
 * @extends AbstractMainDocument<Advice>
 */
#[ORM\Entity(repositoryClass: AdviceMainDocumentRepository::class)]
#[ORM\HasLifecycleCallbacks]
class AdviceMainDocument extends AbstractMainDocument
{
    public function __construct(
        Advice $dossier,
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
            AttachmentType::ADVICE,
        ];
    }
}
