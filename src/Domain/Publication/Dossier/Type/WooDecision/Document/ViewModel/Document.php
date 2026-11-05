<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision\Document\ViewModel;

use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document as EntityDocument;
use Shared\Domain\Publication\Dossier\ViewModel\Ground;

final readonly class Document
{
    /**
     * @param list<Ground> $grounds
     */
    public function __construct(
        public bool $ingested,
        public EntityDocument $entity,
        public bool $isDownloadable,
        public array $grounds,
    ) {
    }
}
