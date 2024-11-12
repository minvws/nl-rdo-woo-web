<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\FileProvider;

class DossierFileProviderException extends \RuntimeException
{
    public static function forNoProviderAvailable(DossierFileType $type): self
    {
        return new self(
            sprintf('No DossierFileProviderInterface implementation available for type: %s', $type->value),
        );
    }
}
