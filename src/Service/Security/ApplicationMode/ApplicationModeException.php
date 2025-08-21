<?php

declare(strict_types=1);

namespace App\Service\Security\ApplicationMode;

class ApplicationModeException extends \RuntimeException
{
    public static function forCannotDetermineAccessibleDossierStatuses(ApplicationMode $applicationMode): self
    {
        return new self(sprintf(
            'Cannot determine accessible dossier statuses for application mode "%s"',
            $applicationMode->value,
        ));
    }
}
