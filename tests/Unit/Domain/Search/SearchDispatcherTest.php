<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Search;

use App\Domain\Search\Index\DeleteElasticDocumentCommand;
use App\Domain\Search\Index\Dossier\IndexDossierCommand;
use App\Domain\Search\Index\SubType\IndexAttachmentCommand;
use App\Domain\Search\Index\SubType\IndexMainDocumentCommand;
use App\Domain\Search\SearchDispatcher;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class SearchDispatcherTest extends UnitTestCase
{
    private MessageBusInterface&MockInterface $messageBus;
    private SearchDispatcher $dispatcher;

    public function setUp(): void
    {
        $this->messageBus = \Mockery::mock(MessageBusInterface::class);

        $this->dispatcher = new SearchDispatcher(
            $this->messageBus,
        );
    }

    public function testDispatchDeleteElasticDocumentCommand(): void
    {
        $id = 'foo-bar-123';

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (DeleteElasticDocumentCommand $command) use ($id) {
                self::assertEquals($id, $command->id);

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->dispatcher->dispatchDeleteElasticDocumentCommand($id);
    }

    public function testDispatchIndexAttachmentCommand(): void
    {
        $id = Uuid::v6();

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (IndexAttachmentCommand $command) use ($id) {
                self::assertEquals($id, $command->uuid);

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->dispatcher->dispatchIndexAttachmentCommand($id);
    }

    public function testDispatchIndexMainDocumentCommand(): void
    {
        $id = Uuid::v6();

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (IndexMainDocumentCommand $command) use ($id) {
                self::assertEquals($id, $command->uuid);

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->dispatcher->dispatchIndexMainDocumentCommand($id);
    }

    public function testDispatchIndexDossierCommand(): void
    {
        $id = Uuid::v6();
        $refresh = false;

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (IndexDossierCommand $command) use ($id, $refresh) {
                self::assertEquals($id, $command->getUuid());
                self::assertEquals($refresh, $command->getRefresh());

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->dispatcher->dispatchIndexDossierCommand($id, $refresh);
    }
}
