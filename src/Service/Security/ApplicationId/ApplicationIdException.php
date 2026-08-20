<?php

declare(strict_types=1);

namespace Shared\Service\Security\ApplicationId;

use RuntimeException;
use Shared\ApplicationId;

use function sprintf;

class ApplicationIdException extends RuntimeException
{
    public static function forCannotDetermineAccessibleDossierStatuses(ApplicationId $applicationId): self
    {
        return new self(sprintf(
            'Cannot determine accessible dossier statuses for application id "%s"',
            $applicationId->value,
        ));
    }
}
