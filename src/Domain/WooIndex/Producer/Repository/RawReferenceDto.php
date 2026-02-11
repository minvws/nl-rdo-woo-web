<?php

declare(strict_types=1);

namespace Shared\Domain\WooIndex\Producer\Repository;

use Shared\Domain\Publication\Dossier\FileProvider\DossierFileType;
use Symfony\Component\Uid\Uuid;

use function is_string;

final readonly class RawReferenceDto
{
    public DossierFileType $source;

    /**
     * @param DossierFileType|value-of<DossierFileType> $source
     */
    public function __construct(
        DossierFileType|string $source,
        public Uuid $id,
        public string $documentFileName,
    ) {
        $this->source = is_string($source)
            ? DossierFileType::from($source)
            : $source;
    }
}
