<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Document\ViewModel;

use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document as EntityDocument;

final readonly class Document
{
    public function __construct(
        public bool $ingested,
        public EntityDocument $entity,
        public bool $withdrawn = false,
    ) {
    }
}
