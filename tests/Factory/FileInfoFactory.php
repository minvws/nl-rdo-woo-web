<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\FileInfo;
use App\SourceType;
use Zenstruck\Foundry\ObjectFactory;

/**
 * @extends ObjectFactory<FileInfo>
 */
final class FileInfoFactory extends ObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        $fileName = 'file_name.pdf';
        $documentIdHash = hash('sha256', self::faker()->uuid());
        $path = sprintf('/%s/%s', substr($documentIdHash, 0, 2), substr($documentIdHash, 2));

        return [
            'sourceType' => self::faker()->randomElement(SourceType::cases()),
            'name' => $fileName,
            'mimetype' => 'application/pdf',
            'type' => 'pdf',
            'uploaded' => true,
            'path' => sprintf('%s/%s', $path, $fileName),
            'size' => 1337,
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this;
    }

    public static function class(): string
    {
        return FileInfo::class;
    }
}
