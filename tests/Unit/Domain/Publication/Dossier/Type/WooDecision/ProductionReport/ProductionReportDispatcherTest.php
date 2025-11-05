<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\ProductionReport;

use App\Domain\Publication\Dossier\Type\WooDecision\Inventory\Command\GenerateInventoryCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\Command\ConfirmProductionReportUpdateCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\Command\InitiateProductionReportUpdateCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\Command\ProductionReportProcessRunCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\Command\RejectProductionReportUpdateCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReportDispatcher;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class ProductionReportDispatcherTest extends UnitTestCase
{
    private MessageBusInterface&MockInterface $messageBus;
    private ProductionReportDispatcher $dispatcher;

    protected function setUp(): void
    {
        $this->messageBus = \Mockery::mock(MessageBusInterface::class);

        $this->dispatcher = new ProductionReportDispatcher(
            $this->messageBus,
        );
    }

    public function testDispatchInitiateProductionReportUpdateCommand(): void
    {
        $wooDecision = \Mockery::mock(WooDecision::class);
        $upload = \Mockery::mock(UploadedFile::class);

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (InitiateProductionReportUpdateCommand $command) use ($wooDecision, $upload) {
                self::assertEquals($wooDecision, $command->dossier);
                self::assertEquals($upload, $command->upload);

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->dispatcher->dispatchInitiateProductionReportUpdateCommand($wooDecision, $upload);
    }

    public function testDispatchProductionReportProcessRunCommand(): void
    {
        $id = Uuid::v6();

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (ProductionReportProcessRunCommand $command) use ($id) {
                self::assertEquals($id, $command->getUuid());

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->dispatcher->dispatchProductionReportProcessRunCommand($id);
    }

    public function testDispatchConfirmProductionReportUpdateCommand(): void
    {
        $wooDecision = \Mockery::mock(WooDecision::class);

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (ConfirmProductionReportUpdateCommand $command) use ($wooDecision) {
                self::assertEquals($wooDecision, $command->dossier);

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->dispatcher->dispatchConfirmProductionReportUpdateCommand($wooDecision);
    }

    public function testDispatchRejectProductionReportUpdateCommand(): void
    {
        $wooDecision = \Mockery::mock(WooDecision::class);

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (RejectProductionReportUpdateCommand $command) use ($wooDecision) {
                self::assertEquals($wooDecision, $command->dossier);

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->dispatcher->dispatchRejectProductionReportUpdateCommand($wooDecision);
    }

    public function testDispatchGenerateInventoryCommand(): void
    {
        $id = Uuid::v6();

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (GenerateInventoryCommand $command) use ($id) {
                self::assertEquals($id, $command->getUuid());

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->dispatcher->dispatchGenerateInventoryCommand($id);
    }
}
