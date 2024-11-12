<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\Covenant;

use App\Domain\Publication\Attachment\AttachmentLanguage;
use App\Domain\Publication\Attachment\AttachmentType;
use App\Domain\Publication\MainDocument\AbstractMainDocument;
use Doctrine\ORM\Mapping as ORM;

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
