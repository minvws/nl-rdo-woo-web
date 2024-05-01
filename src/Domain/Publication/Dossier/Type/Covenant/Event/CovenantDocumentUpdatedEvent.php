<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\Covenant\Event;

use App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument;

readonly class CovenantDocumentUpdatedEvent
{
    public function __construct(
        public CovenantDocument $document,
    ) {
    }
}
