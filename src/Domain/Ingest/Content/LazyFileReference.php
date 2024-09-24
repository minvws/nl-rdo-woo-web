<?php

declare(strict_types=1);

namespace App\Domain\Ingest\Content;

use App\Entity\EntityWithFileInfo;
use App\Service\Storage\EntityStorageService;

class LazyFileReference
{
    /** @var callable */
    private $loader;

    private ?string $path = null;

    public function __construct(callable $loader)
    {
        $this->loader = $loader;
    }

    public function getPath(): string
    {
        if ($this->path === null) {
            $this->path = ($this->loader)();
        }

        return $this->path;
    }

    public function hasPath(): bool
    {
        return $this->path !== null;
    }

    public static function createForEntityWithFileInfo(
        EntityWithFileInfo $entity,
        ContentExtractOptions $options,
        EntityStorageService $entityStorage,
    ): self {
        return new self(
            function () use ($entity, $options, $entityStorage) {
                $filePath = $options->hasPageNumber()
                    ? $entityStorage->downloadPage($entity, $options->getPageNumber())
                    : $entityStorage->downloadEntity($entity);

                if ($filePath === false) {
                    throw ContentExtractException::forCannotCreateLazyFileReference($entity);
                }

                return $filePath;
            }
        );
    }
}
