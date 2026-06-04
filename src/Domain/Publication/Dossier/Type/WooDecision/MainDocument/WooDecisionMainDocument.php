<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision\MainDocument;

use Doctrine\ORM\Mapping as ORM;
use Override;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;
use Shared\ValueObject\PlainDate;

/**
 * @extends AbstractMainDocument<WooDecision>
 */
#[ORM\Entity(repositoryClass: WooDecisionMainDocumentRepository::class)]
#[ORM\HasLifecycleCallbacks]
class WooDecisionMainDocument extends AbstractMainDocument
{
    public function __construct(
        WooDecision $dossier,
        PlainDate $formalDate,
        AttachmentLanguage $language,
    ) {
        parent::__construct();

        $this->dossier = $dossier;
        $this->setFormalDate($formalDate);
        $this->type = AttachmentType::JUDGEMENT_ON_WOB_WOO_REQUEST;
        $this->language = $language;
        $this->fileInfo->setPaginatable(true);
    }

    /**
     * @return list<AttachmentType>
     */
    #[Override]
    public static function getAllowedTypes(): array
    {
        return [AttachmentType::JUDGEMENT_ON_WOB_WOO_REQUEST];
    }

    #[Override]
    public function setFormalDate(PlainDate $formalDate): void
    {
        $this->formalDate = $formalDate;

        // Forward the formal date to the woo-decision decisionDate
        $this->dossier->setDecisionDate($formalDate);
    }
}
