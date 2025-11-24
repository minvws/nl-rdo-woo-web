<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Ingest\Process\Pdf;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Shared\Domain\Ingest\IngestDispatcher;
use Shared\Domain\Ingest\Process\Pdf\IngestPdfCommand;
use Shared\Domain\Ingest\Process\Pdf\IngestPdfHandler;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\EntityWithFileInfo;
use Shared\Domain\Publication\FileInfo;
use Shared\Service\Worker\Pdf\Extractor\PagecountExtractor;
use Shared\Service\Worker\Pdf\Tools\Pdftk\PdftkPageCountResult;
use Shared\Service\Worker\PdfProcessor;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

final class IngestPdfHandlerTest extends UnitTestCase
{
    private EntityManagerInterface&MockInterface $doctrine;
    private PagecountExtractor&MockInterface $extractor;
    private IngestDispatcher&MockInterface $ingestDispatcher;
    private PdfProcessor&MockInterface $processor;
    private LoggerInterface&MockInterface $logger;
    private EntityRepository&MockInterface $repository;
    private IngestPdfHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->doctrine = \Mockery::mock(EntityManagerInterface::class);
        $this->extractor = \Mockery::mock(PagecountExtractor::class);
        $this->ingestDispatcher = \Mockery::mock(IngestDispatcher::class);
        $this->processor = \Mockery::mock(PdfProcessor::class);
        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->repository = \Mockery::mock(EntityRepository::class);

        $this->handler = new IngestPdfHandler(
            $this->doctrine,
            $this->extractor,
            $this->processor,
            $this->logger,
            $this->ingestDispatcher,
        );
    }

    public function testInvoke(): void
    {
        $fileInfo = \Mockery::mock(FileInfo::class);
        $fileInfo->shouldReceive('isPaginatable')->once()->andReturnTrue();
        $fileInfo->shouldReceive('getPageCount')->once()->andReturnNull();

        $entity = \Mockery::mock(Document::class);
        $entity->shouldReceive('getFileInfo')->andReturn($fileInfo);

        $message = new IngestPdfCommand(
            $id = Uuid::v6(),
            $entityClass = $entity::class,
            false,
        );

        $this->doctrine->shouldReceive('getRepository')->once()->with($entityClass)->andReturn($this->repository);
        $this->doctrine->shouldReceive('persist')->with($entity);
        $this->doctrine->shouldReceive('flush');

        $this->repository->shouldReceive('find')->once()->andReturn($entity);

        $this->extractor->shouldReceive('extract')->once()->with($entity);

        $pdftkPageCountResult = \Mockery::mock(PdftkPageCountResult::class);
        $pdftkPageCountResult->shouldReceive('isSuccessful')->once()->andReturnTrue();
        $pdftkPageCountResult->numberOfPages = $pageCount = 2;

        $this->extractor->shouldReceive('getOutPut')->once()->with()->andReturn($pdftkPageCountResult);

        $fileInfo->shouldReceive('setPageCount')->once()->with($pageCount);
        $fileInfo->shouldReceive('getPageCount')->andReturn($pageCount);

        $this->processor->shouldReceive('processEntity')->once()->with($entity);

        $this->ingestDispatcher->expects('dispatchIngestPdfPageCommand')->with($id, $entityClass, 1);
        $this->ingestDispatcher->expects('dispatchIngestPdfPageCommand')->with($id, $entityClass, 2);

        $this->handler->__invoke($message);
    }

    public function testInvokeSkipsPageCountExtractWhenPageCountIsAlreadyAvailable(): void
    {
        $fileInfo = \Mockery::mock(FileInfo::class);
        $fileInfo->shouldReceive('isPaginatable')->once()->andReturnTrue();
        $fileInfo->shouldReceive('getPageCount')->andReturn(2);

        $entity = \Mockery::mock(Document::class);
        $entity->shouldReceive('getFileInfo')->andReturn($fileInfo);

        $message = new IngestPdfCommand(
            $id = Uuid::v6(),
            $entityClass = $entity::class,
            false,
        );

        $this->doctrine->shouldReceive('getRepository')->once()->with($entityClass)->andReturn($this->repository);
        $this->doctrine->shouldReceive('persist')->with($entity);
        $this->doctrine->shouldReceive('flush');

        $this->repository->shouldReceive('find')->once()->andReturn($entity);
        $this->processor->shouldReceive('processEntity')->once()->with($entity);

        $this->ingestDispatcher->expects('dispatchIngestPdfPageCommand')->with($id, $entityClass, 1);
        $this->ingestDispatcher->expects('dispatchIngestPdfPageCommand')->with($id, $entityClass, 2);

        $this->handler->__invoke($message);
    }

    public function testInvokeWhenEntityCannotBeFound(): void
    {
        $message = new IngestPdfCommand(
            $id = Uuid::v6(),
            $entityClass = \Mockery::mock(EntityWithFileInfo::class)::class,
            false,
        );

        // @TODO should be replaced by an instance of EntityWithFileInfo
        $entity = \Mockery::mock(Document::class);

        $this->doctrine->shouldReceive('getRepository')->once()->with($entityClass)->andReturn($this->repository);
        $this->repository->shouldReceive('find')->once()->andReturnNull();

        $this->logger->shouldReceive('warning')->once()->with('No entity found in IngestPdfHandler', [
            'id' => $id->toRfc4122(),
            'class' => $entityClass,
        ]);

        $this->doctrine->shouldNotReceive('persist');
        $this->doctrine->shouldNotReceive('flush');
        $this->extractor->shouldNotReceive('extract');
        $this->extractor->shouldNotReceive('getOutPut');
        $this->processor->shouldNotReceive('processEntity');

        $this->handler->__invoke($message);
    }
}
