<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier;

use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;

readonly class DossierBreadcrumbFactory
{
    /**
     * Returns the translation key to use as breadcrumb title for the given dossier.
     */
    public function forDossier(AbstractDossier $dossier): string
    {
        if ($dossier instanceof WooDecision) {
            $publicationReason = $dossier->getPublicationReason();
            if ($publicationReason !== null) {
                return $publicationReason->getTranslationKey();
            }
        }

        return $dossier->getType()->getTranslationKey();
    }
}
