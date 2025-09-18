<?php

declare(strict_types=1);

namespace App\Service\Security\ApplicationMode;

use App\Domain\Publication\Dossier\DossierStatus;

enum ApplicationMode: string
{
    case PUBLIC = 'FRONTEND';
    case ADMIN = 'BALIE';
    case API = 'API';
    case ALL = 'ALL';

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
            default => throw ApplicationModeException::forCannotDetermineAccessibleDossierStatuses($this),
        };
    }

    public function isAdmin(): bool
    {
        return $this === self::ADMIN;
    }

    public function isApi(): bool
    {
        return $this === self::API;
    }

    public function isPublic(): bool
    {
        return $this === self::PUBLIC;
    }

    public function isAll(): bool
    {
        return $this === self::ALL;
    }

    public static function fromEnvVar(string $value): self
    {
        return self::from(strtoupper($value));
    }
}
