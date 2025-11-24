<?php

declare(strict_types=1);

namespace Shared\Tests\Factory\Publication\Dossier;

use Shared\Domain\Publication\Dossier\DocumentPrefix;
use Shared\Tests\Factory\OrganisationFactory;
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
