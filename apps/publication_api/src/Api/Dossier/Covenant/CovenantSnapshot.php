<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Covenant;

use PublicationApi\Domain\Dossier\MetadataSnapshot;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;

readonly class CovenantSnapshot
{
    private function __construct(
        public ?MetadataSnapshot $mainDocument,
    ) {
    }

    public static function of(Covenant $covenant): self
    {
        return new self(MetadataSnapshot::ofNullable($covenant->getMainDocument()));
    }
}
