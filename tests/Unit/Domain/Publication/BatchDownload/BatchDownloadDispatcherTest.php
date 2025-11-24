<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\BatchDownload;

use Mockery\MockInterface;
use Shared\Domain\Publication\BatchDownload\BatchDownload;
use Shared\Domain\Publication\BatchDownload\BatchDownloadDispatcher;
use Shared\Domain\Publication\BatchDownload\Command\GenerateBatchDownloadCommand;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class BatchDownloadDispatcherTest extends UnitTestCase
{
    private MessageBusInterface&MockInterface $messageBus;
    private BatchDownloadDispatcher $dispatcher;

    protected function setUp(): void
    {
        $this->messageBus = \Mockery::mock(MessageBusInterface::class);

        $this->dispatcher = new BatchDownloadDispatcher(
            $this->messageBus,
        );
    }

    public function testDispatchGenerateBatchDownloadCommand(): void
    {
        $batch = \Mockery::mock(BatchDownload::class);
        $batch->shouldReceive('getId')->andReturn($dossierId = Uuid::v6());

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (GenerateBatchDownloadCommand $event) use ($dossierId) {
                self::assertEquals($dossierId, $event->uuid);

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->dispatcher->dispatchGenerateBatchDownloadCommand($batch);
    }
}
