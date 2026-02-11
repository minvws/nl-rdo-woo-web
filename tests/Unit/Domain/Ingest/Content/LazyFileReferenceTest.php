<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Ingest\Content;

use Mockery;
use Mockery\MockInterface;
use Mockery\VerificationDirector;
use Shared\Domain\Ingest\Content\ContentExtractException;
use Shared\Domain\Ingest\Content\ContentExtractOptions;
use Shared\Domain\Ingest\Content\LazyFileReference;
use Shared\Domain\Publication\EntityWithFileInfo;
use Shared\Service\Storage\EntityStorageService;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

class LazyFileReferenceTest extends UnitTestCase
{
    public function testLazyFileReferenceLoadsFileOnlyWhenNeededAndOnlyOnce(): void
    {
        /** @var callable&MockInterface $spy */
        $spy = Mockery::spy(
            static fn (): string => '/foo/bar.txt'
        );

        $reference = new LazyFileReference($spy);

        $spy->shouldNotHaveBeenCalled();

        self::assertEquals('/foo/bar.txt', $reference->getPath());

        /** @var VerificationDirector $verfication */
        $verfication = $spy->shouldHaveBeenCalled();
        $verfication->once();

        self::assertEquals('/foo/bar.txt', $reference->getPath());

        /** @var VerificationDirector $verfication */
        $verfication = $spy->shouldHaveBeenCalled();
        $verfication->once();
    }

    public function testItThrowsExceptionWhenFilePathCannotBeDetermined(): void
    {
        /** @var EntityWithFileInfo&MockInterface $entity */
        $entity = Mockery::mock(EntityWithFileInfo::class);
        $entity->shouldReceive('getId')->andReturn(Uuid::v6());

        /** @var ContentExtractOptions&MockInterface $options */
        $options = Mockery::mock(ContentExtractOptions::class);
        $options->shouldReceive('hasPageNumber')->once()->andReturn(false);

        /** @var EntityStorageService&MockInterface $entityStorage */
        $entityStorage = Mockery::mock(EntityStorageService::class);
        $entityStorage->shouldReceive('downloadEntity')->once()->with($entity)->andReturn(false);

        $result = LazyFileReference::createForEntityWithFileInfo($entity, $options, $entityStorage);

        $this->expectExceptionObject(ContentExtractException::forCannotCreateLazyFileReference($entity));

        $result->getPath();
    }
}
