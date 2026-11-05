<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Ingest\Content;

use GuzzleHttp\Psr7\Utils;
use Mockery;
use Shared\Domain\Ingest\Content\ContentExtractException;
use Shared\Domain\Ingest\Content\ContentExtractOptions;
use Shared\Domain\Ingest\Content\LazyFileReference;
use Shared\Domain\Publication\EntityWithFileInfo;
use Shared\Service\Storage\EntityStorageService;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

class LazyFileReferenceTest extends UnitTestCase
{
    public function testGetStream(): void
    {
        $entity = Mockery::mock(EntityWithFileInfo::class);
        $stream = Utils::tryFopen('php://memory', 'r');

        $entityStorageService = Mockery::mock(EntityStorageService::class);
        $entityStorageService->expects('retrieveResourceEntity')->with($entity)->andReturn($stream);

        $lazyFileReference = new LazyFileReference($entity, ContentExtractOptions::create(), $entityStorageService);

        self::assertSame($stream, $lazyFileReference->getStream());
    }

    public function testGetStreamThrowsExceptionWhenEntityCannotBeRead(): void
    {
        $entity = Mockery::mock(EntityWithFileInfo::class);
        $entity->expects('getId')->times(2)->andReturn(Uuid::v6());

        $entityStorageService = Mockery::mock(EntityStorageService::class);
        $entityStorageService->expects('retrieveResourceEntity')->with($entity)->andReturnNull();

        $lazyFileReference = new LazyFileReference($entity, ContentExtractOptions::create(), $entityStorageService);

        $this->expectExceptionObject(ContentExtractException::forCannotCreateLazyFileReference($entity));

        $lazyFileReference->getStream();
    }

    public function testGetStreamThrowsExceptionForPage(): void
    {
        $contentExtractOptions = ContentExtractOptions::create()
            ->withPageNumber($this->getFaker()->numberBetween(1, 100));

        $lazyFileReference = new LazyFileReference(
            Mockery::mock(EntityWithFileInfo::class),
            $contentExtractOptions,
            Mockery::mock(EntityStorageService::class),
        );

        $this->expectExceptionObject(ContentExtractException::forCannotCreateLazyFileReferenceForPage());

        $lazyFileReference->getStream();
    }
}
