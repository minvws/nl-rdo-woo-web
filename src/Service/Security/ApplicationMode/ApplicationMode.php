<?php

declare(strict_types=1);

namespace App\Service\Security\ApplicationMode;

use App\Domain\Publication\Dossier\DossierStatus;

enum ApplicationMode
{
    case PUBLIC;
    case ADMIN;

    /**
     * @return list<DossierStatus>
     */
    public function getAccessibleDossierStatuses(): array
    {
        return match ($this) {
            self::PUBLIC => DossierStatus::publiclyAvailableCases(),
            self::ADMIN => [
                DossierStatus::CONCEPT,
                DossierStatus::SCHEDULED,
                DossierStatus::PREVIEW,
                DossierStatus::PUBLISHED,
            ],
        };
    }
}
