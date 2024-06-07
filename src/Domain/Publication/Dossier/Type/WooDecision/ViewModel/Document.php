<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\ViewModel;

use App\Entity\Document as EntityDocument;

final readonly class Document
{
    public function __construct(
        public bool $ingested,
        public EntityDocument $entity,
    ) {
    }
}
