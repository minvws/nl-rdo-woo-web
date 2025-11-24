<?php

declare(strict_types=1);

namespace Shared\Api\Publication\V1\Dossier\WooDecision;

use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Symfony\Component\Validator\Constraints as Assert;

class WooDecisionMainDocumentRequestDto
{
    public \DateTimeImmutable $formalDate;
    /**
     * @var list<string>
     */
    #[Assert\All([
        new Assert\Type('string'),
    ])]
    public array $grounds = [];
    public string $internalReference = '';
    public AttachmentLanguage $language;
}
