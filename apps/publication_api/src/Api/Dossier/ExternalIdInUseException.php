<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier;

use RuntimeException;
use Shared\Domain\Publication\Dossier\Type\DossierType;

use function sprintf;

class ExternalIdInUseException extends RuntimeException
{
    public static function forExternalIdAlreadyUsed(DossierType $type): self
    {
        return new self(sprintf('ExternalId already in use by type %s', $type->value));
    }
}
