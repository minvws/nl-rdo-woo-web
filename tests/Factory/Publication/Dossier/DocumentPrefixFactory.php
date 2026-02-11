<?php

declare(strict_types=1);

namespace Shared\Tests\Factory\Publication\Dossier;

use Shared\Domain\Publication\Dossier\DocumentPrefix;
use Shared\Tests\Factory\OrganisationFactory;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<DocumentPrefix>
 */
final class DocumentPrefixFactory extends PersistentObjectFactory
{
    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'organisation' => OrganisationFactory::new(),
            'prefix' => self::faker()->unique()->word(),
        ];
    }

    public static function class(): string
    {
        return DocumentPrefix::class;
    }
}
