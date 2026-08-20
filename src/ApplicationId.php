<?php

declare(strict_types=1);

namespace Shared;

use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Service\Security\ApplicationId\ApplicationIdException;

use function strtolower;

enum ApplicationId: string
{
    case ADMIN = 'admin';
    case PUBLIC = 'public';
    case PUBLICATION_API = 'publication_api';
    case WORKER = 'worker';
    case SHARED = 'shared';

    public static function fromString(?string $value): self
    {
        if ($value === null || $value === '') {
            return ApplicationId::SHARED;
        }

        return self::from(strtolower($value));
    }

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
            default => throw ApplicationIdException::forCannotDetermineAccessibleDossierStatuses($this),
        };
    }

    public function isAdmin(): bool
    {
        return $this === ApplicationId::ADMIN;
    }

    public function isPublic(): bool
    {
        return $this === ApplicationId::PUBLIC;
    }

    public function isPublicationApi(): bool
    {
        return $this === ApplicationId::PUBLICATION_API;
    }

    public function isWorker(): bool
    {
        return $this === ApplicationId::WORKER;
    }

    public function isShared(): bool
    {
        return $this === ApplicationId::SHARED;
    }
}
