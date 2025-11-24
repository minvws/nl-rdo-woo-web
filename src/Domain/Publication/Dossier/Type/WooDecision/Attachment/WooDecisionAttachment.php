<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision\Attachment;

use Doctrine\ORM\Mapping as ORM;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Webmozart\Assert\Assert;

#[ORM\Entity(repositoryClass: WooDecisionAttachmentRepository::class)]
class WooDecisionAttachment extends AbstractAttachment
{
    public function __construct(
        AbstractDossier $dossier,
        \DateTimeImmutable $formalDate,
        AttachmentType $type,
        AttachmentLanguage $language,
    ) {
        parent::__construct();

        Assert::isInstanceOf($dossier, WooDecision::class);

        $this->dossier = $dossier;
        $this->formalDate = $formalDate;
        $this->type = $type;
        $this->language = $language;
        $this->fileInfo->setPaginatable(true);
    }
}
