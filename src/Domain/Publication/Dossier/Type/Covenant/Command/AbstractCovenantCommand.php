<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\Covenant\Command;

use App\Domain\Publication\Dossier\Type\Covenant\Covenant;

abstract readonly class AbstractCovenantCommand
{
    public function __construct(
        public Covenant $covenant,
    ) {
    }
}
