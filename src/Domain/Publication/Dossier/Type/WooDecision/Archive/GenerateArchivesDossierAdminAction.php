<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Archive;

use App\Domain\Publication\BatchDownload\BatchDownloadScope;
use App\Domain\Publication\BatchDownload\BatchDownloadService;
use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Admin\Action\DossierAdminAction;
use App\Domain\Publication\Dossier\Admin\Action\DossierAdminActionInterface;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Webmozart\Assert\Assert;

readonly class GenerateArchivesDossierAdminAction implements DossierAdminActionInterface
{
    public function __construct(
        private BatchDownloadService $batchDownloadService,
    ) {
    }

    public function getAdminAction(): DossierAdminAction
    {
        return DossierAdminAction::GENERATE_ARCHIVES;
    }

    public function supports(AbstractDossier $dossier): bool
    {
        return $dossier instanceof WooDecision;
    }

    public function execute(AbstractDossier $dossier): void
    {
        Assert::isInstanceOf($dossier, WooDecision::class);

        $this->batchDownloadService->refresh(
            BatchDownloadScope::forWooDecision($dossier),
        );
    }
}
