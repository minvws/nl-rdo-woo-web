<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Worker;

use Mockery;
use Shared\Domain\Ingest\Process\PdfPage\PdfPageProcessingContextFactory;
use Shared\Domain\Publication\EntityWithFileInfo;
use Shared\Domain\Publication\FileInfo;
use Shared\Tests\Unit\UnitTestCase;

class PdfPageProcessingContextFactoryTest extends UnitTestCase
{
    public function testCreateContextSuccessful(): void
    {
        $fileInfo = Mockery::mock(FileInfo::class);
        $fileInfo->expects('isUploaded')->andReturnTrue();

        $entity = Mockery::mock(EntityWithFileInfo::class);
        $entity->expects('getFileInfo')->andReturn($fileInfo);

        $pageNumber = $this->getFaker()->numberBetween(1, 100);

        $context = new PdfPageProcessingContextFactory()->createContext($entity, $pageNumber);

        self::assertNotNull($context);
        self::assertSame($entity, $context->getEntity());
        self::assertSame($pageNumber, $context->getPageNumber());
        self::assertNull($context->getOptionalPageContents());
    }

    public function testCreateContextOnEntityWithoutUploadedFile(): void
    {
        $fileInfo = Mockery::mock(FileInfo::class);
        $fileInfo->expects('isUploaded')->andReturnFalse();

        $entity = Mockery::mock(EntityWithFileInfo::class);
        $entity->expects('getFileInfo')->andReturn($fileInfo);

        $context = new PdfPageProcessingContextFactory()->createContext($entity, $this->getFaker()->numberBetween(1, 100));

        self::assertNull($context);
    }
}
