<?php

declare(strict_types=1);

namespace Shared\Tests\Factory\History;

use DateTimeImmutable;
use Shared\Domain\Publication\History\History;
use Shared\Service\HistoryService;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<History>
 */
final class HistoryFactory extends PersistentObjectFactory
{
    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'type' => HistoryService::TYPE_DOSSIER,
            'identifier' => Uuid::v6(),
            'createdDt' => new DateTimeImmutable(),
            'contextKey' => 'dossier_update_dossier_nr',
            'context' => [],
            'site' => HistoryService::MODE_BOTH,
        ];
    }

    public static function class(): string
    {
        return History::class;
    }
}
