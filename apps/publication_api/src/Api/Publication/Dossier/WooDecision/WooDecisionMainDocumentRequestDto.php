<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\WooDecision;

use DateTimeImmutable;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Symfony\Component\Validator\Constraints as Assert;

class WooDecisionMainDocumentRequestDto
{
    /**
     * @param string[] $grounds
     */
    public function __construct(
        public string $filename,
        public DateTimeImmutable $formalDate,
        #[Assert\All([
            new Assert\Type('string'),
        ])]
        public AttachmentLanguage $language,
        public array $grounds = [],
        public string $internalReference = '',
    ) {
    }
}
