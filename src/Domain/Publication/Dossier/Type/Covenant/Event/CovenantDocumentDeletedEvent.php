<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\Covenant\Event;

use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Symfony\Component\Uid\Uuid;

readonly class CovenantDocumentDeletedEvent
{
    public function __construct(
        public Uuid $covenantDocumentId,
        public Covenant $covenant,
    ) {
    }
}
