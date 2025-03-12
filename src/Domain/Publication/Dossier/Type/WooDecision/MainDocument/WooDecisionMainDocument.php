<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\MainDocument;

use App\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use App\Domain\Publication\Attachment\Enum\AttachmentType;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Publication\MainDocument\AbstractMainDocument;
use Doctrine\ORM\Mapping as ORM;

/**
 * @extends AbstractMainDocument<WooDecision>
 */
#[ORM\Entity(repositoryClass: WooDecisionMainDocumentRepository::class)]
#[ORM\HasLifecycleCallbacks]
class WooDecisionMainDocument extends AbstractMainDocument
{
    public function __construct(
        WooDecision $dossier,
        \DateTimeImmutable $formalDate,
        AttachmentLanguage $language,
    ) {
        parent::__construct();

        $this->dossier = $dossier;
        $this->formalDate = $formalDate;
        $this->type = AttachmentType::JUDGEMENT_ON_WOB_WOO_REQUEST;
        $this->language = $language;
        $this->fileInfo->setPaginatable(true);
    }

    /**
     * @return list<AttachmentType>
     */
    public static function getAllowedTypes(): array
    {
        return [AttachmentType::JUDGEMENT_ON_WOB_WOO_REQUEST];
    }
}
