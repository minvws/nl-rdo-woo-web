<?php

namespace App\Tests\Factory;

use App\Entity\FileInfo;
use App\SourceType;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;

/**
 * @extends ModelFactory<FileInfo>
 *
 * @method        FileInfo|Proxy     create(array|callable $attributes = [])
 * @method static FileInfo|Proxy     createOne(array $attributes = [])
 * @method static FileInfo[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static FileInfo[]|Proxy[] createSequence(iterable|callable $sequence)
 *
 * @phpstan-method        Proxy<FileInfo> create(array|callable $attributes = [])
 * @phpstan-method static Proxy<FileInfo> createOne(array $attributes = [])
 * @phpstan-method static list<Proxy<FileInfo>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<Proxy<FileInfo>> createSequence(iterable|callable $sequence)
 */
final class FileInfoFactory extends ModelFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function getDefaults(): array
    {
        $fileName = 'file_name.pdf';
        $documentIdHash = hash('sha256', self::faker()->uuid());
        $path = sprintf('/%s/%s', substr($documentIdHash, 0, 2), substr($documentIdHash, 2));

        return [
            'sourceType' => self::faker()->randomElement(SourceType::getAllSourceTypes()),
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
    protected function initialize(): self
    {
        return $this
            ->withoutPersisting()
            // ->afterInstantiate(function(FileInfo $fileInfo): void {})
        ;
    }

    protected static function getClass(): string
    {
        return FileInfo::class;
    }
}
