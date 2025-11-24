<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision\Document\ViewModel;

use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document as EntityDocument;

final readonly class Document
{
    public function __construct(
        public bool $ingested,
        public EntityDocument $entity,
        public bool $withdrawn = false,
    ) {
    }
}
