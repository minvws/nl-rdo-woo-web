<?php

declare(strict_types=1);

namespace App\Tests\Factory\Publication\Subject;

use App\Domain\Publication\Subject\Subject;
use App\Tests\Factory\OrganisationFactory;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Subject>
 */
final class SubjectFactory extends PersistentProxyObjectFactory
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
