<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Domain\Publication\FileInfo;
use App\Service\Storage\StorageRootPathGenerator;
use App\SourceType;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\ObjectFactory;

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
    #[\Override]
    protected function initialize(): static
    {
        return $this;
    }

    public static function class(): string
    {
        return FileInfo::class;
    }
}
