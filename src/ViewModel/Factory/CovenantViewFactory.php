<?php

declare(strict_types=1);

namespace App\ViewModel\Factory;

use App\Domain\Publication\Dossier\Type\Covenant\Covenant as CovenantEntity;
use App\ViewModel\Covenant;
use Webmozart\Assert\Assert;

final readonly class CovenantViewFactory
{
    public function make(CovenantEntity $dossier): Covenant
    {
        $title = $dossier->getTitle();
        Assert::notNull($title);

        $publicationDate = $dossier->getPublicationDate();
        Assert::notNull($publicationDate);

        return new Covenant(
            entity: $dossier,
            dossierId: $dossier->getId()->toRfc4122(),
            dossierNr: $dossier->getDossierNr(),
            isPreview: $dossier->getStatus()->isPreview(),
            title: $title,
            pageTitle: $dossier->getStatus()->isPreview()
                ? sprintf('%s %s', $title, '(preview)')
                : $title,
            publicationDate: $publicationDate,
            summary: $dossier->getSummary(),
        );
    }
}
