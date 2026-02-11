<?php

declare(strict_types=1);

namespace Shared\Tests\Integration;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Shared\Domain\Publication\EntityWithFileInfo;
use Webmozart\Assert\Assert;

use function dirname;
use function sprintf;
use function trim;

trait VfsStreamHelpers
{
    private function createFileForEntityOnVfs(
        EntityWithFileInfo $entity,
        string $pathPrefix,
        string $contents = 'This is a test file.',
    ): void {
        Assert::true(
            isset($this->root),
            sprintf('The "root" propery should exist and be an instance of "%s"', vfsStreamDirectory::class),
        );

        $fileInfoPath = $entity->getFileInfo()->getPath();
        Assert::string($fileInfoPath);

        $newDirectoryPath = sprintf('%s/%s', trim($pathPrefix, '/'), trim(dirname($fileInfoPath), '/'));
        vfsStream::newDirectory($newDirectoryPath)->at($this->root);

        /** @var vfsStreamDirectory $childDir */
        $childDir = $this->root->getChild($newDirectoryPath);

        $fileInfoName = $entity->getFileInfo()->getName();
        Assert::string($fileInfoName);

        vfsStream::newFile($fileInfoName)->withContent($contents)->at($childDir);
    }
}
