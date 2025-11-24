<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\ViewModel;

readonly class DossierNotifications
{
    private const string INCOMPLETE = 'incomplete';
    private const string SUSPENDED = 'suspended';
    private const string WITHDRAWN = 'withdrawn';

    public function __construct(
        public bool $isNotCompleted,
        public int $missingUploads,
        public int $withdrawnDocuments,
        public int $suspendedDocuments,
    ) {
    }

    public function hasAnyDocumentNotifications(): bool
    {
        return $this->missingUploads > 0 || $this->withdrawnDocuments > 0 || $this->suspendedDocuments > 0;
    }

    /**
     * @return list<string>
     */
    public function getDossierNotifications(): array
    {
        $notifications = [];
        if ($this->isNotCompleted) {
            $notifications[] = self::INCOMPLETE;
        }
        if ($this->suspendedDocuments > 0) {
            $notifications[] = self::SUSPENDED;
        }
        if ($this->withdrawnDocuments > 0) {
            $notifications[] = self::WITHDRAWN;
        }

        return $notifications;
    }
}
