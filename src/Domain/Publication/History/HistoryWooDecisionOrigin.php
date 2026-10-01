<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\History;

use Shared\Domain\Publication\Dossier\Type\DossierReference;

readonly class HistoryWooDecisionOrigin
{
    public function __construct(
        public DossierReference $dossierReference,
        public bool $isPubliclyAvailable,
    ) {
    }
}
