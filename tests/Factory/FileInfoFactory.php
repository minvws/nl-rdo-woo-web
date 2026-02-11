<?php

declare(strict_types=1);

namespace Shared\Tests\Factory;

use Override;
use Shared\Domain\Publication\FileInfo;
use Shared\Domain\Publication\SourceType;
use Shared\Service\Storage\StorageRootPathGenerator;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\ObjectFactory;

use function sprintf;

/**
 * @extends ObjectFactory<FileInfo>
 */
final class FileInfoFactory extends ObjectFactory
{
    public function __construct(private readonly StorageRootPathGenerator $storageRootPathGenerator)
    {
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        $fileName = 'file_name.pdf';

        return [
            'sourceType' => self::faker()->randomElement(SourceType::cases()),
            'name' => $fileName,
            'mimetype' => 'application/pdf',
            'type' => 'pdf',
            'uploaded' => true,
            'path' => sprintf('%s/%s', $this->storageRootPathGenerator->fromUuid(Uuid::v6()), $fileName),
            'size' => 1337,
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
        return FileInfo::class;
    }
}
