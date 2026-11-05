<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\NoticeNotPublic\ViewModel;

use Shared\Domain\Publication\Dossier\ViewModel\Ground;

readonly class NoticeNotPublic
{
    /**
     * @param list<Ground> $grounds
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
