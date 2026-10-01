<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Advice;

use PublicationApi\Domain\Dossier\MetadataSnapshot;
use Shared\Domain\Publication\Dossier\Type\Advice\Advice;

readonly class AdviceSnapshot
{
    private function __construct(
        public ?MetadataSnapshot $mainDocument,
    ) {
    }

    public static function of(Advice $advice): self
    {
        return new self(MetadataSnapshot::ofNullable($advice->getMainDocument()));
    }
}
