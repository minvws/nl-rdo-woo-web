<?php

declare(strict_types=1);

namespace Shared\Tests\Factory;

use DateTimeImmutable;
use Override;
use Shared\Service\Stats\WorkerStats;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<WorkerStats>
 */
final class WorkerStatsFactory extends PersistentObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'section' => self::faker()->randomElement(['foo', 'bar']),
            'createdAt' => DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'duration' => self::faker()->numberBetween(10_000),
            'hostname' => 'localhost',
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[Override]
    protected function initialize(): static
    {
        return $this;
    }

    public static function class(): string
    {
        return WorkerStats::class;
    }
}
