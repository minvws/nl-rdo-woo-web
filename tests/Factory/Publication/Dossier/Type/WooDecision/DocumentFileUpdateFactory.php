<?php

declare(strict_types=1);

namespace Shared\Tests\Factory\Publication\Dossier\Type\WooDecision;

use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileUpdate;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Enum\DocumentFileUpdateStatus;
use Shared\Tests\Factory\DocumentFactory;
use Shared\Tests\Factory\FileInfoFactory;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<DocumentFileUpdate>
 */
final class DocumentFileUpdateFactory extends PersistentProxyObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'createdAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'updatedAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'status' => DocumentFileUpdateStatus::PENDING,
            'fileInfo' => FileInfoFactory::new(),
            'document' => DocumentFactory::new(),
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
        return DocumentFileUpdate::class;
    }
}
