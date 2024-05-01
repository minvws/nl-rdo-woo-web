<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Event;

use Symfony\Component\Uid\Uuid;

abstract readonly class AbstractCovenantEvent
{
    public function __construct(
        public Uuid $id,
    ) {
    }
}
