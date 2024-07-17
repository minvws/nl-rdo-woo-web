<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Search\Index\SubType;

use App\Domain\Ingest\IngestOptions;
use App\Domain\Ingest\SubType\SubTypeIngester;
use App\Domain\Publication\Attachment\AbstractAttachment;
use App\Domain\Publication\Attachment\AbstractAttachmentRepository;
use App\Domain\Search\Index\SubType\IndexAttachmentCommand;
use App\Domain\Search\Index\SubType\IndexAttachmentHandler;
use App\Domain\Search\Index\SubType\SubTypeIndexer;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

class IndexAttachmentHandlerTest extends MockeryTestCase
{
    private AbstractAttachmentRepository&MockInterface $repository;
    private SubTypeIndexer&MockInterface $subTypeIndexer;
    private LoggerInterface&MockInterface $logger;
    private SubTypeIngester&MockInterface $subTypeIngester;
    private IndexAttachmentHandler $handler;

    public function setUp(): void
    {
        $this->repository = \Mockery::mock(AbstractAttachmentRepository::class);
        $this->subTypeIndexer = \Mockery::mock(SubTypeIndexer::class);
        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->subTypeIngester = \Mockery::mock(SubTypeIngester::class);

        $this->handler = new IndexAttachmentHandler(
            $this->repository,
            $this->subTypeIndexer,
            $this->logger,
            $this->subTypeIngester,
        );
    }

    public function testInvokeReturnsEarlyIfNoAttachmentIsFound(): void
    {
        $id = Uuid::v6();
        $attachment = \Mockery::mock(AbstractAttachment::class);
        $attachment->shouldReceive('getId')->andReturn($id);
        $command = IndexAttachmentCommand::forAttachment($attachment);

        $this->repository->expects('find')->with($id)->andReturnNull();
        $this->logger->expects('warning');

        $this->handler->__invoke($command);
    }

    public function testInvokeLogsException(): void
    {
        $id = Uuid::v6();
        $attachment = \Mockery::mock(AbstractAttachment::class);
        $attachment->shouldReceive('getId')->andReturn($id);
        $command = IndexAttachmentCommand::forAttachment($attachment);

        $this->repository->expects('find')->with($id)->andThrow(new \RuntimeException('oops'));
        $this->logger->expects('error');

        $this->handler->__invoke($command);
    }

    public function testInvokeSuccessful(): void
    {
        $id = Uuid::v6();
        $attachment = \Mockery::mock(AbstractAttachment::class);
        $attachment->shouldReceive('getId')->andReturn($id);
        $command = IndexAttachmentCommand::forAttachment($attachment);

        $this->repository->expects('find')->with($id)->andReturn($attachment);
        $this->subTypeIndexer->expects('index')->with($attachment);
        $this->subTypeIngester->expects('ingest')->with($attachment, \Mockery::type(IngestOptions::class));

        $this->handler->__invoke($command);
    }
}
