<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type;

use RuntimeException;

use function sprintf;

class DossierTypeException extends RuntimeException
{
    public static function forDossierTypeNotAvailable(DossierType $type): self
    {
        return new self('No config found for DossierType: ' . $type->value);
    }

    public static function forRouteNotAvailable(DossierType $type): self
    {
        return new self(sprintf('No route available for DossierType: %s', $type->value));
    }

    public static function forAccessDeniedToType(DossierType $type): self
    {
        return new self('User has no access to DossierType: ' . $type->value);
    }
}
