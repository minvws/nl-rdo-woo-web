<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision\Inventory;

use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Admin\Action\DossierAdminAction;
use Shared\Domain\Publication\Dossier\Admin\Action\DossierAdminActionInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReportDispatcher;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Webmozart\Assert\Assert;

readonly class GenerateInventoryDossierAdminAction implements DossierAdminActionInterface
{
    public function __construct(
        private ProductionReportDispatcher $dispatcher,
    ) {
    }

    public function getAdminAction(): DossierAdminAction
    {
        return DossierAdminAction::GENERATE_INVENTORY;
    }

    public function supports(AbstractDossier $dossier): bool
    {
        return $dossier instanceof WooDecision;
    }

    public function execute(AbstractDossier $dossier): void
    {
        Assert::isInstanceOf($dossier, WooDecision::class);

        $this->dispatcher->dispatchGenerateInventoryCommand($dossier->getId());
    }

    public function needsConfirmation(): bool
    {
        return false;
    }
}
