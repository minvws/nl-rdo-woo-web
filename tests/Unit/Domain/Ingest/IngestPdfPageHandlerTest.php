<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Ingest;

use App\Domain\Ingest\IngestPdfPageHandler;
use App\Domain\Ingest\IngestPdfPageMessage;
use App\Entity\Document;
use App\Entity\EntityWithFileInfo;
use App\Service\Worker\PdfProcessor;
use App\Tests\Unit\UnitTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

final class IngestPdfPageHandlerTest extends UnitTestCase
{
    private PdfProcessor&MockInterface $processor;
    private EntityManagerInterface&MockInterface $doctrine;
    private LoggerInterface&MockInterface $logger;
    private EntityRepository&MockInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = \Mockery::mock(PdfProcessor::class);
        $this->doctrine = \Mockery::mock(EntityManagerInterface::class);
        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->repository = \Mockery::mock(EntityRepository::class);
    }

    public function testInvokeWithoutForce(): void
    {
        $message = new IngestPdfPageMessage(
            \Mockery::mock(Uuid::class),
            $entityClass = \Mockery::mock(EntityWithFileInfo::class)::class,
            $pageNr = 101,
            $forceRefresh = false,
        );

        // @TODO should be replaced by an instance of EntityWithFileInfo
        $entity = \Mockery::mock(Document::class);

        $this->doctrine->shouldReceive('getRepository')->once()->with($entityClass)->andReturn($this->repository);
        $this->repository->shouldReceive('find')->once()->andReturn($entity);

        $this->processor->shouldReceive('processDocumentpage')->once()->with($entity, $pageNr, $forceRefresh);

        $handler = new IngestPdfPageHandler($this->processor, $this->doctrine, $this->logger);
        $handler->__invoke($message);
    }

    public function testInvokeWithForce(): void
    {
        $message = new IngestPdfPageMessage(
            \Mockery::mock(Uuid::class),
            $entityClass = \Mockery::mock(EntityWithFileInfo::class)::class,
            $pageNr = 101,
            $forceRefresh = true,
        );

        // @TODO should be replaced by an instance of EntityWithFileInfo
        $entity = \Mockery::mock(Document::class);

        $this->doctrine->shouldReceive('getRepository')->once()->with($entityClass)->andReturn($this->repository);
        $this->repository->shouldReceive('find')->once()->andReturn($entity);

        $this->processor->shouldReceive('processDocumentpage')->once()->with($entity, $pageNr, $forceRefresh);

        $handler = new IngestPdfPageHandler($this->processor, $this->doctrine, $this->logger);
        $handler->__invoke($message);
    }

    public function testInvokeWhenEntityCannotBeFound(): void
    {
        $message = new IngestPdfPageMessage(
            $id = \Mockery::mock(Uuid::class),
            $entityClass = \Mockery::mock(EntityWithFileInfo::class)::class,
            $pageNr = 101,
        );

        $this->doctrine->shouldReceive('getRepository')->once()->with($entityClass)->andReturn($this->repository);
        $this->repository->shouldReceive('find')->once()->andReturnNull();
        $this->logger->shouldReceive('warning')->once()->with('No document found for this message', [
            'id' => $id,
            'class' => $entityClass,
            'pageNr' => $pageNr,
        ]);

        $this->processor->shouldNotReceive('processDocumentpage');

        $handler = new IngestPdfPageHandler($this->processor, $this->doctrine, $this->logger);
        $handler->__invoke($message);
    }

    public function testInvokeWhenProcessDocumentPageThrowsAnException(): void
    {
        $message = new IngestPdfPageMessage(
            $id = \Mockery::mock(Uuid::class),
            $entityClass = \Mockery::mock(EntityWithFileInfo::class)::class,
            $pageNr = 101,
        );

        // @TODO should be replaced by an instance of EntityWithFileInfo
        $entity = \Mockery::mock(Document::class);

        $this->doctrine->shouldReceive('getRepository')->once()->with($entityClass)->andReturn($this->repository);
        $this->repository->shouldReceive('find')->once()->andReturn($entity);

        $this->processor->shouldReceive('processDocumentpage')->once()->andThrow($thrownException = new \RuntimeException('My exception'));

        $this->logger->shouldReceive('error')->once()->with('Error processing document', [
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
