<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Worker\Pdf;

use GuzzleHttp\Psr7\Utils;
use Mockery;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Shared\Domain\Publication\EntityWithFileInfo;
use Shared\Service\Storage\EntityStorageService;
use Shared\Service\Worker\Pdf\Extractor\PagecountExtractor;
use Shared\Service\Worker\Pdf\Tools\Pdftk\PdftkPageCountResult;
use Shared\Service\Worker\Pdf\Tools\Pdftk\PdftkService;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

final class PagecountExtractorTest extends UnitTestCase
{
    protected LoggerInterface&MockInterface $logger;
    protected PdftkService&MockInterface $pdftkService;
    protected EntityStorageService&MockInterface $entityStorageService;
    protected EntityWithFileInfo&MockInterface $entity;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->pdftkService = Mockery::mock(PdftkService::class);
        $this->entityStorageService = Mockery::mock(EntityStorageService::class);
        $this->entity = Mockery::mock(EntityWithFileInfo::class);
    }

    public function testExtract(): void
    {
        $this->entityStorageService
            ->expects('retrieveResourceEntity')
            ->with($this->entity)
            ->andReturn($source = Utils::tryFopen('php://memory', 'r'));

        $pdftkPageCountResult = new PdftkPageCountResult(
            exitCode: 0,
            params: ['cmd', 'param1', 'param2'],
            errorMessage: null,
            numberOfPages: 42,
        );

        $this->pdftkService
            ->expects('extractNumberOfPages')
            ->with($source)
            ->andReturn($pdftkPageCountResult);

        $extractor = new PagecountExtractor(
            $this->logger,
            $this->pdftkService,
            $this->entityStorageService,
        );

        $extractor->extract($this->entity);

        $this->assertSame($pdftkPageCountResult, $extractor->getOutput());
    }

    public function testExtractWithFailedReadingOfEntity(): void
    {
        $this->entityStorageService
            ->expects('retrieveResourceEntity')
            ->with($this->entity)
            ->andReturnNull();

        $this->entity
            ->expects('getId')
            ->andReturn($entityUuid = Mockery::mock(Uuid::class));

        $this->logger
            ->expects('error')
            ->with('Failed to read entity from storage for page count extraction', [
                'id' => $entityUuid,
                'class' => $this->entity::class,
            ]);

        $extractor = new PagecountExtractor(
            $this->logger,
            $this->pdftkService,
            $this->entityStorageService,
        );

        $extractor->extract($this->entity);

        $this->assertNull($extractor->getOutput());
    }

    public function testExtractWithFailedNumberOfPagesExtraction(): void
    {
        $this->entityStorageService
            ->expects('retrieveResourceEntity')
            ->with($this->entity)
            ->andReturn($source = Utils::tryFopen('php://memory', 'r'));

        $pdftkPageCountResult = new PdftkPageCountResult(
            exitCode: 1,
            params: [],
            errorMessage: 'errorMessage',
            numberOfPages: null,
        );

        $this->pdftkService
            ->expects('extractNumberOfPages')
            ->with($source)
            ->andReturn($pdftkPageCountResult);

        $this->entity
            ->expects('getId')
            ->andReturn($entityUuid = Mockery::mock(Uuid::class));

        $this->logger
            ->expects('error')
            ->with('Failed to get number of pages', [
                'id' => $entityUuid,
                'class' => $this->entity::class,
                'errorOutput' => $pdftkPageCountResult->errorMessage,
            ]);

        $extractor = new PagecountExtractor(
            $this->logger,
            $this->pdftkService,
            $this->entityStorageService,
        );

        $extractor->extract($this->entity);

        $this->assertSame($pdftkPageCountResult, $extractor->getOutput());
    }
}
