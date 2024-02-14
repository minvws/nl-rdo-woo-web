<?php

declare(strict_types=1);

namespace App\ViewModel\Factory;

use App\Entity\Dossier as DossierEntity;
use App\Repository\DossierRepository;
use App\ViewModel\Dossier;

final readonly class DossierViewFactory
{
    public function __construct(
        private DossierRepository $dossierRepository,
    ) {
    }

    public function getDossierViewModel(DossierEntity $dossierEntity): Dossier
    {
        return new Dossier(
            entity: $dossierEntity,
            counts: $this->dossierRepository->getDossierCounts($dossierEntity),
        );
    }
}
