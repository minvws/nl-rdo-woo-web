<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision;

use App\Domain\Publication\Dossier\Type\WooDecision\Command\ProcessDocumentFileSetUpdatesCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\Command\ProcessDocumentFileSetUploadsCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\Command\ProcessDocumentFileUpdateCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\Command\ProcessDocumentFileUploadCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFileDispatcher;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\DocumentFileSet;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\DocumentFileUpdate;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\DocumentFileUpload;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class DocumentFileDispatcherTest extends UnitTestCase
{
    private MessageBusInterface&MockInterface $messageBus;
    private DocumentFileDispatcher $dispatcher;

    public function setUp(): void
    {
        $this->messageBus = \Mockery::mock(MessageBusInterface::class);

        $this->dispatcher = new DocumentFileDispatcher(
            $this->messageBus,
        );
    }

    public function testDispatchProcessDocumentFileSetUploadsCommand(): void
    {
        $documentFileSet = \Mockery::mock(DocumentFileSet::class);
        $documentFileSet->shouldReceive('getId')->andReturn($id = Uuid::v6());

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (ProcessDocumentFileSetUploadsCommand $command) use ($id) {
                self::assertEquals($id, $command->documentFileSetId);

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->dispatcher->dispatchProcessDocumentFileSetUploadsCommand($documentFileSet);
    }

    public function testDispatchProcessDocumentFileUploadCommand(): void
    {
        $documentFileUpload = \Mockery::mock(DocumentFileUpload::class);
        $documentFileUpload->shouldReceive('getId')->andReturn($id = Uuid::v6());

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (ProcessDocumentFileUploadCommand $command) use ($id) {
                self::assertEquals($id, $command->id);

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->dispatcher->dispatchProcessDocumentFileUploadCommand($documentFileUpload);
    }

    public function testDispatchProcessDocumentFileSetUpdatesCommand(): void
    {
        $documentFileSet = \Mockery::mock(DocumentFileSet::class);
        $documentFileSet->shouldReceive('getId')->andReturn($id = Uuid::v6());

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (ProcessDocumentFileSetUpdatesCommand $command) use ($id) {
                self::assertEquals($id, $command->documentFileSetId);

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->dispatcher->dispatchProcessDocumentFileSetUpdatesCommand($documentFileSet);
    }

    public function testDispatchProcessDocumentFileUpdateCommand(): void
    {
        $documentFileUpdate = \Mockery::mock(DocumentFileUpdate::class);
        $documentFileUpdate->shouldReceive('getId')->andReturn($id = Uuid::v6());

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (ProcessDocumentFileUpdateCommand $command) use ($id) {
                self::assertEquals($id, $command->id);

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->dispatcher->dispatchProcessDocumentFileUpdateCommand($documentFileUpdate);
    }
}
