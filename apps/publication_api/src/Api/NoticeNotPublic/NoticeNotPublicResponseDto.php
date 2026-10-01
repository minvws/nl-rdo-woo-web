<?php

declare(strict_types=1);

namespace PublicationApi\Api\NoticeNotPublic;

use ApiPlatform\Metadata\ApiProperty;
use Shared\ValueObject\PlainDate;
use Symfony\Component\Uid\Uuid;

final readonly class NoticeNotPublicResponseDto
{
    /**
     * @param list<string> $grounds
     */
    public function __construct(
        #[ApiProperty(description: 'The unique identifier of the notice.')]
        public Uuid $id,
        #[ApiProperty(description: 'The formal date of the original document, for example the date it was adopted or signed.')]
        public PlainDate $formalDate,
        #[ApiProperty(description: 'The name of the original document, if known.')]
        public ?string $documentName,
        #[ApiProperty(description: 'The Woo exemption grounds applied to the document.')]
        public array $grounds,
        #[ApiProperty(description: 'An explanation for why the document will not be made public.')]
        public ?string $explanation,
    ) {
    }
}
