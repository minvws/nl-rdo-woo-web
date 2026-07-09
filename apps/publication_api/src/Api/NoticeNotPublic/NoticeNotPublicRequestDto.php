<?php

declare(strict_types=1);

namespace PublicationApi\Api\NoticeNotPublic;

use Shared\Domain\Publication\Citation;
use Shared\ValueObject\PlainDate;
use Symfony\Component\Validator\Constraints as Assert;

class NoticeNotPublicRequestDto
{
    /**
     * @param list<string> $grounds
     */
    public function __construct(
        public PlainDate $formalDate,
        public ?string $documentName = null,
        #[Assert\NotBlank]
        #[Assert\All([
            new Assert\Choice(choices: Citation::ALL_GROUND_KEYS),
        ])]
        public array $grounds = [],
        public ?string $explanation = null,
    ) {
    }
}
