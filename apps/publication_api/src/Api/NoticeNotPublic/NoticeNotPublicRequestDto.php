<?php

declare(strict_types=1);

namespace PublicationApi\Api\NoticeNotPublic;

use ApiPlatform\Metadata\ApiProperty;
use Shared\Domain\Publication\Ground;
use Shared\ValueObject\PlainDate;
use Symfony\Component\Validator\Constraints as Assert;

class NoticeNotPublicRequestDto
{
    /**
     * @param list<Ground> $grounds
     */
    public function __construct(
        #[ApiProperty(description: 'The formal date of the original document, for example the date it was adopted or signed (format YYYY-MM-DD).')]
        public PlainDate $formalDate,
        #[ApiProperty(description: 'The name of the original document, if known.')]
        public ?string $documentName = null,
        #[Assert\NotBlank]
        #[ApiProperty(description: 'The Woo exemption grounds applied to the document.')]
        public array $grounds = [],
        #[ApiProperty(description: 'An explanation for why the document will not be made public.')]
        public ?string $explanation = null,
    ) {
    }
}
