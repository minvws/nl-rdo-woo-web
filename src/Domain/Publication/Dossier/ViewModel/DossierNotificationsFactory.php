<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\ViewModel;

use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;

readonly class DossierNotificationsFactory
{
    public function __construct(
        private WooDecisionRepository $wooDecisionRepository,
    ) {
    }

    public function make(AbstractDossier $dossier): DossierNotifications
    {
        $counts = null;
        if ($dossier instanceof WooDecision) {
            $counts = $this->wooDecisionRepository->getNotificationCounts($dossier);
        }

        return new DossierNotifications(
            ! $dossier->isCompleted(),
            $counts ? $counts['missing_uploads'] : 0,
            $counts ? $counts['withdrawn'] : 0,
            $counts ? $counts['suspended'] : 0,
        );
    }
}
