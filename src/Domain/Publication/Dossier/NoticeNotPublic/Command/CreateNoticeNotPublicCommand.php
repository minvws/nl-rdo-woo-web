<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\NoticeNotPublic\Command;

use Shared\ValueObject\PlainDate;
use Symfony\Component\Uid\Uuid;

readonly class CreateNoticeNotPublicCommand
{
    /**
     * @param list<string> $grounds
     */
    public function __construct(
        public Uuid $dossierId,
        public ?string $documentName,
        public PlainDate $formalDate,
        public array $grounds,
        public ?string $explanation,
    ) {
    }
}
