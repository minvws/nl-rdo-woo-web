<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\Covenant;

use Doctrine\ORM\Mapping as ORM;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;

/**
 * @extends AbstractMainDocument<Covenant>
 */
#[ORM\Entity(repositoryClass: CovenantMainDocumentRepository::class)]
#[ORM\HasLifecycleCallbacks]
class CovenantMainDocument extends AbstractMainDocument
{
    public function __construct(
        Covenant $dossier,
        \DateTimeImmutable $formalDate,
        AttachmentLanguage $language,
    ) {
        parent::__construct();

        $this->dossier = $dossier;
        $this->formalDate = $formalDate;
        $this->type = AttachmentType::COVENANT;
        $this->language = $language;
        $this->fileInfo->setPaginatable(true);
    }

    /**
     * @return list<AttachmentType>
     */
    public static function getAllowedTypes(): array
    {
        return [AttachmentType::COVENANT];
    }
}
