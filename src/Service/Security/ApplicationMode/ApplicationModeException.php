<?php

declare(strict_types=1);

namespace Shared\Service\Security\ApplicationMode;

use RuntimeException;

use function sprintf;

class ApplicationModeException extends RuntimeException
{
    public static function forCannotDetermineAccessibleDossierStatuses(ApplicationMode $applicationMode): self
    {
        return new self(sprintf(
            'Cannot determine accessible dossier statuses for application mode "%s"',
            $applicationMode->value,
        ));
    }
}
