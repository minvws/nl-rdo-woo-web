<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Admin;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Admin\Action\DossierAdminAction;
use App\Domain\Publication\Dossier\Admin\Action\DossierAdminActionInterface;
use App\Domain\Publication\Dossier\Type\WooDecision\ProductionReportDispatcher;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
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
}
