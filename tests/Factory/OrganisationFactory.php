<?php

declare(strict_types=1);

namespace Shared\Tests\Factory;

use DateTimeImmutable;
use Override;
use Shared\Domain\Organisation\Organisation;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Organisation>
 */
final class OrganisationFactory extends PersistentObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'createdAt' => DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'departments' => [DepartmentFactory::new()],
            'name' => self::faker()->text(255),
            'updatedAt' => DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
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
        return Organisation::class;
    }
}
