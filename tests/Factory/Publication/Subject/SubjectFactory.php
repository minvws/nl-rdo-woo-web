<?php

declare(strict_types=1);

namespace Shared\Tests\Factory\Publication\Subject;

use Shared\Domain\Publication\Subject\Subject;
use Shared\Tests\Factory\OrganisationFactory;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Subject>
 */
final class SubjectFactory extends PersistentObjectFactory
{
    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'organisation' => OrganisationFactory::new(),
            'name' => self::faker()->word(),
        ];
    }

    public static function class(): string
    {
        return Subject::class;
    }
}
