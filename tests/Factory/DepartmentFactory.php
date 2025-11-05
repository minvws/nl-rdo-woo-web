<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Domain\Department\Department;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Department>
 */
final class DepartmentFactory extends PersistentProxyObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        /** @var string $name */
        $name = self::faker()->unique()->words(nb: 6, asText: true);

        return [
            'name' => sprintf('%s %s', $name, 'Department'),
            'shortTag' => self::faker()->unique()->word(),
            'slug' => self::faker()->unique()->slug(3),
            'public' => true,
            'createdAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'updatedAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'fileInfo' => FileInfoFactory::new(),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[\Override]
    protected function initialize(): static
    {
        return $this;
    }

    public static function class(): string
    {
        return Department::class;
    }
}
