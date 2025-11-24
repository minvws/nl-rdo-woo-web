<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision\ProductionReport;

use Shared\Domain\Publication\Dossier\Type\WooDecision\Inventory\Command\GenerateInventoryCommand;
use Shared\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\Command\ConfirmProductionReportUpdateCommand;
use Shared\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\Command\InitiateProductionReportUpdateCommand;
use Shared\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\Command\ProductionReportProcessRunCommand;
use Shared\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\Command\RejectProductionReportUpdateCommand;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

readonly class ProductionReportDispatcher
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    public function dispatchInitiateProductionReportUpdateCommand(WooDecision $wooDecision, UploadedFile $upload): void
    {
        $this->messageBus->dispatch(
            new InitiateProductionReportUpdateCommand($wooDecision, $upload),
        );
    }

    public function dispatchProductionReportProcessRunCommand(Uuid $id): void
    {
        $this->messageBus->dispatch(
            new ProductionReportProcessRunCommand($id),
        );
    }

    public function dispatchConfirmProductionReportUpdateCommand(WooDecision $wooDecision): void
    {
        $this->messageBus->dispatch(
            new ConfirmProductionReportUpdateCommand($wooDecision),
        );
    }

    public function dispatchRejectProductionReportUpdateCommand(WooDecision $wooDecision): void
    {
        $this->messageBus->dispatch(
            new RejectProductionReportUpdateCommand($wooDecision),
        );
    }

    public function dispatchGenerateInventoryCommand(Uuid $id): void
    {
        $this->messageBus->dispatch(
            new GenerateInventoryCommand($id),
        );
    }
}
