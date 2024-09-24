<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\ViewModel;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\AbstractDossierRepository;
use App\Entity\Department;

readonly class DossierViewFactory
{
    public function __construct(
        private AbstractDossierRepository $dossierRepository,
    ) {
    }

    /**
     * @return array<array-key,RecentDossier>
     */
    public function getRecentDossiers(int $limit): array
    {
        return $this->mapToRecentDossier(
            $this->dossierRepository->getRecentDossiers($limit, null),
        );
    }

    /**
     * @return array<array-key,RecentDossier>
     */
    public function getRecentDossiersForDepartment(int $limit, Department $department): array
    {
        return $this->mapToRecentDossier(
            $this->dossierRepository->getRecentDossiers($limit, $department),
        );
    }

    /**
     * @param array<array-key,AbstractDossier> $dossiers
     *
     * @return array<array-key,RecentDossier>
     */
    private function mapToRecentDossier(array $dossiers): array
    {
        return array_map(
            static fn (AbstractDossier $dossier) => RecentDossier::create($dossier),
            $dossiers,
        );
    }
}
