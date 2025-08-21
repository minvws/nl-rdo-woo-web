<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\BatchDownload\Handler;

use App\Domain\Publication\BatchDownload\BatchDownload;
use App\Domain\Publication\BatchDownload\BatchDownloadRepository;
use App\Domain\Publication\BatchDownload\BatchDownloadZipGenerator;
use App\Domain\Publication\BatchDownload\Command\GenerateBatchDownloadCommand;
use App\Domain\Publication\BatchDownload\Handler\GenerateBatchDownloadHandler;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

class GenerateBatchDownloadHandlerTest extends MockeryTestCase
{
    private BatchDownloadRepository&MockInterface $repository;
    private BatchDownloadZipGenerator&MockInterface $zipGenerator;
    private LoggerInterface&MockInterface $logger;
    private GenerateBatchDownloadHandler $handler;

    public function setUp(): void
    {
        $this->repository = \Mockery::mock(BatchDownloadRepository::class);
        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->zipGenerator = \Mockery::mock(BatchDownloadZipGenerator::class);

        $this->handler = new GenerateBatchDownloadHandler(
            $this->repository,
            $this->logger,
            $this->zipGenerator,
        );
    }

    public function testInvoke(): void
    {
        $uuid = Uuid::v6();
        $batchDownload = \Mockery::mock(BatchDownload::class);

        $this->repository
            ->shouldReceive('find')
            ->with($uuid)
            ->andReturn($batchDownload);

        $this->zipGenerator
            ->expects('generateArchive')
            ->with($batchDownload)
            ->andReturnTrue();

        $this->handler->__invoke(new GenerateBatchDownloadCommand($uuid));
    }

    public function testWarningIsLoggedWhenBatchDownloadCannotBeFound(): void
    {
        $uuid = Uuid::v6();

        $this->repository
            ->shouldReceive('find')
            ->with($uuid)
            ->andReturnNull();

        $this->logger->expects('error');

        $this->handler->__invoke(new GenerateBatchDownloadCommand($uuid));
    }
}
