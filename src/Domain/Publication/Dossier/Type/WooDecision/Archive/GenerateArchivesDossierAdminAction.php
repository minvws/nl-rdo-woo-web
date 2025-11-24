<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision\Archive;

use Shared\Domain\Publication\BatchDownload\BatchDownloadScope;
use Shared\Domain\Publication\BatchDownload\BatchDownloadService;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Admin\Action\DossierAdminAction;
use Shared\Domain\Publication\Dossier\Admin\Action\DossierAdminActionInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
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

    public function needsConfirmation(): bool
    {
        return false;
    }
}
