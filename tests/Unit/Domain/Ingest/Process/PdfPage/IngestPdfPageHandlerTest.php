<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Ingest\Process\PdfPage;

use App\Domain\Ingest\Process\PdfPage\IngestPdfPageCommand;
use App\Domain\Ingest\Process\PdfPage\IngestPdfPageHandler;
use App\Domain\Ingest\Process\PdfPage\PdfPageProcessor;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Publication\EntityWithFileInfo;
use App\Tests\Unit\UnitTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

final class IngestPdfPageHandlerTest extends UnitTestCase
{
    private PdfPageProcessor&MockInterface $processor;
    private EntityManagerInterface&MockInterface $doctrine;
    private LoggerInterface&MockInterface $logger;
    private EntityRepository&MockInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = \Mockery::mock(PdfPageProcessor::class);
        $this->doctrine = \Mockery::mock(EntityManagerInterface::class);
        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->repository = \Mockery::mock(EntityRepository::class);
    }

    public function testInvokeWithoutForce(): void
    {
        $message = new IngestPdfPageCommand(
            \Mockery::mock(Uuid::class),
            $entityClass = \Mockery::mock(EntityWithFileInfo::class)::class,
            $forceRefresh = false,
            $pageNr = 101,
        );

        // @TODO should be replaced by an instance of EntityWithFileInfo
        $entity = \Mockery::mock(Document::class);

        $this->doctrine->shouldReceive('getRepository')->once()->with($entityClass)->andReturn($this->repository);
        $this->repository->shouldReceive('find')->once()->andReturn($entity);

        $this->processor->shouldReceive('processPage')->once()->with($entity, $pageNr, $forceRefresh);

        $handler = new IngestPdfPageHandler($this->processor, $this->doctrine, $this->logger);
        $handler->__invoke($message);
    }

    public function testInvokeWithForce(): void
    {
        $message = new IngestPdfPageCommand(
            \Mockery::mock(Uuid::class),
            $entityClass = \Mockery::mock(EntityWithFileInfo::class)::class,
            $forceRefresh = true,
            $pageNr = 101,
        );

        // @TODO should be replaced by an instance of EntityWithFileInfo
        $entity = \Mockery::mock(Document::class);

        $this->doctrine->shouldReceive('getRepository')->once()->with($entityClass)->andReturn($this->repository);
        $this->repository->shouldReceive('find')->once()->andReturn($entity);

        $this->processor->shouldReceive('processPage')->once()->with($entity, $pageNr, $forceRefresh);

        $handler = new IngestPdfPageHandler($this->processor, $this->doctrine, $this->logger);
        $handler->__invoke($message);
    }

    public function testInvokeWhenEntityCannotBeFound(): void
    {
        $message = new IngestPdfPageCommand(
            $id = \Mockery::mock(Uuid::class),
            $entityClass = \Mockery::mock(EntityWithFileInfo::class)::class,
            false,
            $pageNr = 101,
        );

        $this->doctrine->shouldReceive('getRepository')->once()->with($entityClass)->andReturn($this->repository);
        $this->repository->shouldReceive('find')->once()->andReturnNull();
        $this->logger->shouldReceive('warning')->once()->with('No entity found in IngestPdfPageHandler', [
            'id' => $id,
            'class' => $entityClass,
            'pageNr' => $pageNr,
        ]);

        $this->processor->shouldNotReceive('processEntityPage');

        $handler = new IngestPdfPageHandler($this->processor, $this->doctrine, $this->logger);
        $handler->__invoke($message);
    }

    public function testInvokeWhenProcessDocumentPageThrowsAnException(): void
    {
        $message = new IngestPdfPageCommand(
            $id = \Mockery::mock(Uuid::class),
            $entityClass = \Mockery::mock(EntityWithFileInfo::class)::class,
            false,
            $pageNr = 101,
        );

        // @TODO should be replaced by an instance of EntityWithFileInfo
        $entity = \Mockery::mock(Document::class);

        $this->doctrine->shouldReceive('getRepository')->once()->with($entityClass)->andReturn($this->repository);
        $this->repository->shouldReceive('find')->once()->andReturn($entity);

        $this->processor->shouldReceive('processPage')->once()->andThrow($thrownException = new \RuntimeException('My exception'));

        $this->logger->shouldReceive('error')->once()->with('Error processing document in IngestPdfPageHandler', [
            'id' => $id,
            'class' => $entityClass,
            'pageNr' => $pageNr,
            'exception' => $thrownException->getMessage(),
        ]);

        $this->expectExceptionObject($thrownException);

        $handler = new IngestPdfPageHandler($this->processor, $this->doctrine, $this->logger);
        $handler->__invoke($message);
    }
}
