<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\ViewModel;

use Shared\Domain\Department\Department;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\DossierRepository;

use function array_map;

readonly class DossierViewFactory
{
    public function __construct(
        private DossierRepository $dossierRepository,
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
            RecentDossier::create(...),
            $dossiers,
        );
    }
}
