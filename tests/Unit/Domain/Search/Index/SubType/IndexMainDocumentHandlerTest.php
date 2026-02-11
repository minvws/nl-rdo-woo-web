<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Search\Index\SubType;

use Mockery;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Shared\Domain\Ingest\Process\IngestProcessOptions;
use Shared\Domain\Ingest\Process\SubType\SubTypeIngester;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;
use Shared\Domain\Publication\MainDocument\MainDocumentRepository;
use Shared\Domain\Search\Index\SubType\IndexMainDocumentCommand;
use Shared\Domain\Search\Index\SubType\IndexMainDocumentHandler;
use Shared\Domain\Search\Index\SubType\SubTypeIndexer;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

class IndexMainDocumentHandlerTest extends UnitTestCase
{
    private MainDocumentRepository&MockInterface $repository;
    private SubTypeIndexer&MockInterface $subTypeIndexer;
    private LoggerInterface&MockInterface $logger;
    private SubTypeIngester&MockInterface $subTypeIngester;
    private IndexMainDocumentHandler $handler;

    protected function setUp(): void
    {
        $this->repository = Mockery::mock(MainDocumentRepository::class);
        $this->subTypeIndexer = Mockery::mock(SubTypeIndexer::class);
        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->subTypeIngester = Mockery::mock(SubTypeIngester::class);

        $this->handler = new IndexMainDocumentHandler(
            $this->repository,
            $this->subTypeIndexer,
            $this->logger,
            $this->subTypeIngester,
        );
    }

    public function testInvokeReturnsEarlyIfNoMainDocumentIsFound(): void
    {
        $id = Uuid::v6();
        $command = new IndexMainDocumentCommand($id);

        $this->repository->expects('find')->with($id)->andReturnNull();
        $this->logger->expects('warning');

        $this->handler->__invoke($command);
    }

    public function testInvokeLogsException(): void
    {
        $id = Uuid::v6();
        $command = new IndexMainDocumentCommand($id);

        $this->repository->expects('find')->with($id)->andThrow(new RuntimeException('oops'));
        $this->logger->expects('error');

        $this->handler->__invoke($command);
    }

    public function testInvokeSuccessful(): void
    {
        $id = Uuid::v6();
        $mainDocument = Mockery::mock(AbstractMainDocument::class);
        $command = new IndexMainDocumentCommand($id);

        $this->repository->expects('find')->with($id)->andReturn($mainDocument);
        $this->subTypeIndexer->expects('index')->with($mainDocument);
        $this->subTypeIngester->expects('ingest')->with($mainDocument, Mockery::type(IngestProcessOptions::class));

        $this->handler->__invoke($command);
    }
}
