<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\FileProvider;

use RuntimeException;

use function sprintf;

class DossierFileProviderException extends RuntimeException
{
    public static function forNoProviderAvailable(DossierFileType $type): self
    {
        return new self(
            sprintf('No DossierFileProviderInterface implementation available for type: %s', $type->value),
        );
    }
}
