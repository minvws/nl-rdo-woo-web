<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision;

use App\Domain\Publication\Dossier\Type\WooDecision\Command\ProcessDocumentCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\Command\RemoveDocumentCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\Command\ReplaceDocumentCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\Command\WithDrawDocumentCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentDispatcher;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\WooDecision;
use App\Domain\Publication\Dossier\Type\WooDecision\WithdrawReason;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class DocumentDispatcherTest extends UnitTestCase
{
    private MessageBusInterface&MockInterface $messageBus;
    private DocumentDispatcher $dispatcher;

    public function setUp(): void
    {
        $this->messageBus = \Mockery::mock(MessageBusInterface::class);

        $this->dispatcher = new DocumentDispatcher(
            $this->messageBus,
        );
    }

    public function testDispatchProcessDocumentCommand(): void
    {
        $id = Uuid::v6();
        $remotePath = '/foo/prefix-bar.pdf';
        $originalFileName = 'bar.pdf';
        $chunked = true;
        $chunkUuid = Uuid::v6()->toRfc4122();
        $chunkCount = 3;

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (ProcessDocumentCommand $command) use ($id, $remotePath, $originalFileName, $chunked, $chunkUuid, $chunkCount) {
                self::assertEquals($id, $command->getDossierUuid());
                self::assertEquals($remotePath, $command->getRemotePath());
                self::assertEquals($originalFileName, $command->getOriginalFilename());
                self::assertEquals($chunked, $command->isChunked());
                self::assertEquals($chunkUuid, $command->getChunkUuid());
                self::assertEquals($chunkCount, $command->getChunkCount());

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->dispatcher->dispatchProcessDocumentCommand(
            $id,
            $remotePath,
            $originalFileName,
            $chunked,
            $chunkUuid,
            $chunkCount,
        );
    }

    public function testDispatchRemoveDocumentCommand(): void
    {
        $dossierId = Uuid::v6();
        $documentId = Uuid::v6();

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (RemoveDocumentCommand $command) use ($dossierId, $documentId) {
                self::assertEquals($dossierId, $command->getDossierId());
                self::assertEquals($documentId, $command->getDocumentId());

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->dispatcher->dispatchRemoveDocumentCommand(
            $dossierId,
            $documentId,
        );
    }

    public function testDispatchReplaceDocumentCommand(): void
    {
        $dossierId = Uuid::v6();
        $documentId = Uuid::v6();
        $remotePath = '/foo/prefix-bar.pdf';
        $originalFileName = 'bar.pdf';
        $chunked = true;
        $chunkUuid = Uuid::v6()->toRfc4122();
        $chunkCount = 3;

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (ReplaceDocumentCommand $command) use (
                $dossierId,
                $documentId,
                $remotePath,
                $originalFileName,
                $chunked,
                $chunkUuid,
                $chunkCount,
            ) {
                self::assertEquals($dossierId, $command->getDossierUuid());
                self::assertEquals($documentId, $command->getDocumentUuid());
                self::assertEquals($remotePath, $command->getRemotePath());
                self::assertEquals($originalFileName, $command->getOriginalFilename());
                self::assertEquals($chunked, $command->isChunked());
                self::assertEquals($chunkUuid, $command->getChunkUuid());
                self::assertEquals($chunkCount, $command->getChunkCount());

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->dispatcher->dispatchReplaceDocumentCommand(
            $dossierId,
            $documentId,
            $remotePath,
            $originalFileName,
            $chunked,
            $chunkUuid,
            $chunkCount,
        );
    }

    public function testDispatchWithdrawDocumentCommand(): void
    {
        $wooDecision = \Mockery::mock(WooDecision::class);
        $document = \Mockery::mock(Document::class);
        $reason = WithdrawReason::DATA_IN_DOCUMENT;
        $explanation = 'oops';

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (WithDrawDocumentCommand $command) use ($wooDecision, $document, $reason, $explanation) {
                self::assertEquals($wooDecision, $command->dossier);
                self::assertEquals($document, $command->document);
                self::assertEquals($reason, $command->reason);
                self::assertEquals($explanation, $command->explanation);

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->dispatcher->dispatchWithdrawDocumentCommand(
            $wooDecision,
            $document,
            $reason,
            $explanation,
        );
    }
}
