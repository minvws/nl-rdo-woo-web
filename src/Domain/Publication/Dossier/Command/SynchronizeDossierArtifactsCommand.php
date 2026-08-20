<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Command;

use Shared\Domain\Event\DeduplicatableEvent;

use function sprintf;

readonly class SynchronizeDossierArtifactsCommand extends AbstractDossierReferenceCommand implements DeduplicatableEvent
{
    public function deduplicationKey(): string
    {
        return sprintf('%s:%s', static::class, $this->uuid->toRfc4122());
    }
}
