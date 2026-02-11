<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\DocumentFile;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Command\ProcessDocumentFileSetUpdatesCommand;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Command\ProcessDocumentFileSetUploadsCommand;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Command\ProcessDocumentFileUpdateCommand;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Command\ProcessDocumentFileUploadCommand;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\DocumentFileDispatcher;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileSet;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileUpdate;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileUpload;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Event\DocumentFileSetProcessedEvent;
use Shared\Tests\Unit\UnitTestCase;
use stdClass;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class DocumentFileDispatcherTest extends UnitTestCase
{
    private MessageBusInterface&MockInterface $messageBus;
    private DocumentFileDispatcher $dispatcher;

    protected function setUp(): void
    {
        $this->messageBus = Mockery::mock(MessageBusInterface::class);

        $this->dispatcher = new DocumentFileDispatcher(
            $this->messageBus,
        );
    }

    public function testDispatchProcessDocumentFileSetUploadsCommand(): void
    {
        $documentFileSet = Mockery::mock(DocumentFileSet::class);
        $documentFileSet->shouldReceive('getId')->andReturn($id = Uuid::v6());

        $this->messageBus->expects('dispatch')->with(Mockery::on(
            static function (ProcessDocumentFileSetUploadsCommand $command) use ($id) {
                self::assertEquals($id, $command->documentFileSetId);

                return true;
            }
        ))->andReturns(new Envelope(new stdClass()));

        $this->dispatcher->dispatchProcessDocumentFileSetUploadsCommand($documentFileSet);
    }

    public function testDispatchProcessDocumentFileUploadCommand(): void
    {
        $documentFileUpload = Mockery::mock(DocumentFileUpload::class);
        $documentFileUpload->shouldReceive('getId')->andReturn($id = Uuid::v6());

        $this->messageBus->expects('dispatch')->with(Mockery::on(
            static function (ProcessDocumentFileUploadCommand $command) use ($id) {
                self::assertEquals($id, $command->id);

                return true;
            }
        ))->andReturns(new Envelope(new stdClass()));

        $this->dispatcher->dispatchProcessDocumentFileUploadCommand($documentFileUpload);
    }

    public function testDispatchProcessDocumentFileSetUpdatesCommand(): void
    {
        $documentFileSet = Mockery::mock(DocumentFileSet::class);
        $documentFileSet->shouldReceive('getId')->andReturn($id = Uuid::v6());

        $this->messageBus->expects('dispatch')->with(Mockery::on(
            static function (ProcessDocumentFileSetUpdatesCommand $command) use ($id) {
                self::assertEquals($id, $command->documentFileSetId);

                return true;
            }
        ))->andReturns(new Envelope(new stdClass()));

        $this->dispatcher->dispatchProcessDocumentFileSetUpdatesCommand($documentFileSet);
    }

    public function testDispatchProcessDocumentFileUpdateCommand(): void
    {
        $documentFileUpdate = Mockery::mock(DocumentFileUpdate::class);
        $documentFileUpdate->shouldReceive('getId')->andReturn($id = Uuid::v6());

        $this->messageBus->expects('dispatch')->with(Mockery::on(
            static function (ProcessDocumentFileUpdateCommand $command) use ($id) {
                self::assertEquals($id, $command->id);

                return true;
            }
        ))->andReturns(new Envelope(new stdClass()));

        $this->dispatcher->dispatchProcessDocumentFileUpdateCommand($documentFileUpdate);
    }

    public function testDispatchDocumentFileSetProcessedEvent(): void
    {
        $documentFileSet = Mockery::mock(DocumentFileSet::class);
        $documentFileSet->shouldReceive('getDossier->getId')->andReturn($id = Uuid::v6());

        $this->messageBus->expects('dispatch')->with(Mockery::on(
            static function (DocumentFileSetProcessedEvent $command) use ($id) {
                self::assertEquals($id, $command->dossierId);

                return true;
            }
        ))->andReturns(new Envelope(new stdClass()));

        $this->dispatcher->dispatchDocumentFileSetProcessedEvent($documentFileSet);
    }
}
