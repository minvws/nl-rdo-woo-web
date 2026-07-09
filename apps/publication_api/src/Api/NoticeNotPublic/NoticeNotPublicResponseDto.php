<?php

declare(strict_types=1);

namespace PublicationApi\Api\NoticeNotPublic;

use Shared\ValueObject\PlainDate;
use Symfony\Component\Uid\Uuid;

final readonly class NoticeNotPublicResponseDto
{
    /**
     * @param list<string> $grounds
     */
    public function __construct(
        public Uuid $id,
        public PlainDate $formalDate,
        public ?string $documentName,
        public array $grounds,
        public ?string $explanation,
    ) {
    }
}
