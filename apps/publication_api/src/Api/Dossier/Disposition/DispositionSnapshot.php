<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Disposition;

use PublicationApi\Domain\Dossier\MetadataSnapshot;
use Shared\Domain\Publication\Dossier\Type\Disposition\Disposition;

readonly class DispositionSnapshot
{
    private function __construct(
        public ?MetadataSnapshot $mainDocument,
    ) {
    }

    public static function of(Disposition $disposition): self
    {
        return new self(MetadataSnapshot::ofNullable($disposition->getMainDocument()));
    }
}
