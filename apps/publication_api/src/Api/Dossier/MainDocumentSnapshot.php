<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier;

use PublicationApi\Domain\Dossier\MetadataSnapshot;
use Shared\Domain\Publication\MainDocument\EntityWithMainDocument;

readonly class MainDocumentSnapshot
{
    private function __construct(
        public ?MetadataSnapshot $mainDocument,
    ) {
    }

    public static function of(EntityWithMainDocument $entityWithMainDocument): self
    {
        return new self(MetadataSnapshot::ofNullable($entityWithMainDocument->getMainDocument()));
    }
}
