<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Search\Index\SubType;

use App\Domain\Ingest\Process\IngestProcessOptions;
use App\Domain\Ingest\Process\SubType\SubTypeIngester;
use App\Domain\Publication\MainDocument\AbstractMainDocument;
use App\Domain\Publication\MainDocument\AbstractMainDocumentRepository;
use App\Domain\Search\Index\SubType\IndexMainDocumentCommand;
use App\Domain\Search\Index\SubType\IndexMainDocumentHandler;
use App\Domain\Search\Index\SubType\SubTypeIndexer;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

class IndexMainDocumentHandlerTest extends MockeryTestCase
{
    private AbstractMainDocumentRepository&MockInterface $repository;
    private SubTypeIndexer&MockInterface $subTypeIndexer;
    private LoggerInterface&MockInterface $logger;
    private SubTypeIngester&MockInterface $subTypeIngester;
    private IndexMainDocumentHandler $handler;

    public function setUp(): void
    {
        $this->repository = \Mockery::mock(AbstractMainDocumentRepository::class);
        $this->subTypeIndexer = \Mockery::mock(SubTypeIndexer::class);
        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->subTypeIngester = \Mockery::mock(SubTypeIngester::class);

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
        $mainDocument = \Mockery::mock(AbstractMainDocument::class);
        $mainDocument->shouldReceive('getId')->andReturn($id);
        $command = IndexMainDocumentCommand::forMainDocument($mainDocument);

        $this->repository->expects('find')->with($id)->andReturnNull();
        $this->logger->expects('warning');

        $this->handler->__invoke($command);
    }

    public function testInvokeLogsException(): void
    {
        $id = Uuid::v6();
        $mainDocument = \Mockery::mock(AbstractMainDocument::class);
        $mainDocument->shouldReceive('getId')->andReturn($id);
        $command = IndexMainDocumentCommand::forMainDocument($mainDocument);

        $this->repository->expects('find')->with($id)->andThrow(new \RuntimeException('oops'));
        $this->logger->expects('error');

        $this->handler->__invoke($command);
    }

    public function testInvokeSuccessful(): void
    {
        $id = Uuid::v6();
        $mainDocument = \Mockery::mock(AbstractMainDocument::class);
        $mainDocument->shouldReceive('getId')->andReturn($id);
        $command = IndexMainDocumentCommand::forMainDocument($mainDocument);

        $this->repository->expects('find')->with($id)->andReturn($mainDocument);
        $this->subTypeIndexer->expects('index')->with($mainDocument);
        $this->subTypeIngester->expects('ingest')->with($mainDocument, \Mockery::type(IngestProcessOptions::class));

        $this->handler->__invoke($command);
    }
}
