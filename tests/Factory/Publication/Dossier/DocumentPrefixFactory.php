<?php

declare(strict_types=1);

namespace App\Tests\Factory\Publication\Dossier;

use App\Domain\Publication\Dossier\DocumentPrefix;
use App\Tests\Factory\OrganisationFactory;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<DocumentPrefix>
 */
final class DocumentPrefixFactory extends PersistentProxyObjectFactory
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
