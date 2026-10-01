<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\RequestForAdvice;

use PublicationApi\Domain\Dossier\MetadataSnapshot;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdvice;

readonly class RequestForAdviceSnapshot
{
    private function __construct(
        public ?MetadataSnapshot $mainDocument,
    ) {
    }

    public static function of(RequestForAdvice $requestForAdvice): self
    {
        return new self(MetadataSnapshot::ofNullable($requestForAdvice->getMainDocument()));
    }
}
