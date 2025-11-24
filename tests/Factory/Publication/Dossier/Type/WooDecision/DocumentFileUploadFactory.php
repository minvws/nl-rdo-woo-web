<?php

declare(strict_types=1);

namespace Shared\Tests\Factory\Publication\Dossier\Type\WooDecision;

use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileUpload;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Enum\DocumentFileUploadStatus;
use Shared\Tests\Factory\FileInfoFactory;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<DocumentFileUpload>
 */
final class DocumentFileUploadFactory extends PersistentProxyObjectFactory
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
            'status' => DocumentFileUploadStatus::UPLOADED,
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
        return DocumentFileUpload::class;
    }
}
