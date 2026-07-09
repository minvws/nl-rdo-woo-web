<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\NoticeNotPublic\ViewModel;

readonly class NoticeNotPublic
{
    /**
     * @param list<string> $grounds
     */
    public function __construct(
        public string $id,
        public ?string $documentName,
        public string $formalDate,
        public array $grounds,
        public ?string $explanation,
        public string $detailsUrl,
        public string $title,
    ) {
    }
}
