<?php

declare(strict_types=1);

namespace Shared\Service\Security\ApplicationMode;

use Shared\Domain\Publication\Dossier\DossierStatus;

enum ApplicationMode: string
{
    case PUBLIC = 'FRONTEND';
    case ADMIN = 'BALIE';
    case WORKER = 'WORKER';
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
        return $this === ApplicationMode::ADMIN;
    }

    public function isAdminOrAll(): bool
    {
        return $this->isEnvironmentOrAll(ApplicationMode::ADMIN);
    }

    public function isApi(): bool
    {
        return $this === ApplicationMode::API;
    }

    public function isApiOrAll(): bool
    {
        return $this->isEnvironmentOrAll(ApplicationMode::API);
    }

    public function isPublic(): bool
    {
        return $this === ApplicationMode::PUBLIC;
    }

    public function isPublicOrAll(): bool
    {
        return $this->isEnvironmentOrAll(ApplicationMode::PUBLIC);
    }

    public function isWorker(): bool
    {
        return $this === ApplicationMode::WORKER;
    }

    public function isWorkerOrAll(): bool
    {
        return $this->isEnvironmentOrAll(ApplicationMode::WORKER);
    }

    public function isAll(): bool
    {
        return $this === ApplicationMode::ALL;
    }

    public static function fromEnvVar(string $value): self
    {
        return self::from(strtoupper($value));
    }

    private function isEnvironmentOrAll(ApplicationMode $applicationMode): bool
    {
        return $this === $applicationMode || $this->isAll();
    }
}
