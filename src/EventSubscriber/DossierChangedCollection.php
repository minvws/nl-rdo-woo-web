<?php

declare(strict_types=1);

namespace Shared\EventSubscriber;

use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Service\ResetInterface;

use function array_values;

class DossierChangedCollection implements ResetInterface
{
    /** @var array<string, Uuid> */
    private array $pending = [];

    private bool $processing = false;

    public function addDossierId(Uuid $dossierId): void
    {
        $this->pending[$dossierId->toRfc4122()] = $dossierId;
    }

    /**
     * @return array<array-key, Uuid>
     */
    public function claim(): array
    {
        if ($this->processing || $this->pending === []) {
            return [];
        }

        $this->processing = true;

        return array_values($this->pending);
    }

    public function removeDossierIdFromCollection(Uuid $dossierId): void
    {
        unset($this->pending[$dossierId->toRfc4122()]);
    }

    public function reset(): void
    {
        $this->pending = [];
        $this->processing = false;
    }
}
