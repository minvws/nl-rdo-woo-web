<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\OtherPublication;

use PublicationApi\Domain\Dossier\MetadataSnapshot;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublication;

readonly class OtherPublicationSnapshot
{
    private function __construct(
        public ?MetadataSnapshot $mainDocument,
    ) {
    }

    public static function of(OtherPublication $otherPublication): self
    {
        return new self(MetadataSnapshot::ofNullable($otherPublication->getMainDocument()));
    }
}
