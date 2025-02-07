<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Command\CreateDossierCommand;
use App\Domain\Publication\Dossier\Command\DeleteDossierCommand;
use App\Domain\Publication\Dossier\Command\UpdateDossierContentCommand;
use App\Domain\Publication\Dossier\Command\UpdateDossierDetailsCommand;
use App\Domain\Publication\Dossier\Command\UpdateDossierPublicationCommand;
use App\Domain\Publication\Dossier\DossierDispatcher;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class DossierDispatcherTest extends UnitTestCase
{
    private MessageBusInterface&MockInterface $messageBus;
    private DossierDispatcher $dispatcher;

    public function setUp(): void
    {
        $this->messageBus = \Mockery::mock(MessageBusInterface::class);

        $this->dispatcher = new DossierDispatcher(
            $this->messageBus,
        );
    }

    public function testDispatchCreateDossierCommand(): void
    {
        $dossier = \Mockery::mock(AbstractDossier::class);

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (CreateDossierCommand $command) use ($dossier) {
                self::assertEquals($dossier, $command->dossier);

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->dispatcher->dispatchCreateDossierCommand($dossier);
    }

    public function testDispatchUpdateDossierDetailsCommand(): void
    {
        $dossier = \Mockery::mock(AbstractDossier::class);

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (UpdateDossierDetailsCommand $command) use ($dossier) {
                self::assertEquals($dossier, $command->dossier);

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->dispatcher->dispatchUpdateDossierDetailsCommand($dossier);
    }

    public function testDispatchUpdateDossierContentCommand(): void
    {
        $dossier = \Mockery::mock(AbstractDossier::class);

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (UpdateDossierContentCommand $command) use ($dossier) {
                self::assertEquals($dossier, $command->dossier);

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->dispatcher->dispatchUpdateDossierContentCommand($dossier);
    }

    public function testDispatchUpdateDossierPublicationCommand(): void
    {
        $dossier = \Mockery::mock(AbstractDossier::class);

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (UpdateDossierPublicationCommand $command) use ($dossier) {
                self::assertEquals($dossier, $command->dossier);

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->dispatcher->dispatchUpdateDossierPublicationCommand($dossier);
    }

    public function testDispatchDeleteDossierCommand(): void
    {
        $id = Uuid::v6();

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (DeleteDossierCommand $command) use ($id) {
                self::assertEquals($id, $command->getUuid());

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->dispatcher->dispatchDeleteDossierCommand($id);
    }
}
