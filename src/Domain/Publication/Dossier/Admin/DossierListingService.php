<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Admin;

use Doctrine\ORM\QueryBuilder;
use Shared\Domain\Department\Department;
use Shared\Domain\Publication\Dossier\DossierRepository;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\DossierType;
use Shared\Domain\Publication\Dossier\Type\DossierTypeConfigInterface;
use Shared\Domain\Publication\Dossier\Type\DossierTypeManager;
use Shared\Service\Security\Authorization\AuthorizationMatrix;
use Shared\Service\Security\Authorization\AuthorizationMatrixFilter;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class DossierListingService
{
    public function __construct(
        private DossierRepository $dossierRepository,
        private AuthorizationMatrix $authorizationMatrix,
        private DossierTypeManager $dossierTypeManager,
        private DossierQueryConditions $queryConditions,
        private TranslatorInterface $translator,
    ) {
    }

    public function getFilteredListingQuery(?DossierFilterParameters $filterParameters): QueryBuilder
    {
        $queryBuilder = $this->dossierRepository->getDossiersForOrganisationQueryBuilder(
            organisation: $this->authorizationMatrix->getActiveOrganisation(),
            statuses: $this->getAllowedStatuses(),
            types: $this->getAvailableTypes(),
        );

        if ($filterParameters === null) {
            return $queryBuilder;
        }

        if (count($filterParameters->statuses) > 0) {
            $this->queryConditions->filterOnStatuses(
                $queryBuilder,
                ...$filterParameters->statuses
            );
        }

        if (count($filterParameters->types) > 0) {
            $this->queryConditions->filterOnTypes(
                $queryBuilder,
                ...$filterParameters->types
            );
        }

        if ($filterParameters->departments !== null && ! $filterParameters->departments->isEmpty()) {
            /** @var Department[] $departments */
            $departments = $filterParameters->departments->toArray();

            $this->queryConditions->filterOnDepartments($queryBuilder, ...$departments);
        }

        return $queryBuilder;
    }

    /**
     * @return DossierStatus[]
     */
    public function getAllowedStatuses(): array
    {
        $statuses = [];

        if ($this->authorizationMatrix->hasFilter(AuthorizationMatrixFilter::PUBLISHED_DOSSIERS)) {
            $statuses = array_merge($statuses, DossierStatus::nonConceptCases());
        }

        if ($this->authorizationMatrix->hasFilter(AuthorizationMatrixFilter::UNPUBLISHED_DOSSIERS)) {
            $statuses = array_merge($statuses, DossierStatus::conceptCases());
        }

        return $statuses;
    }

    /**
     * @return DossierType[]
     */
    public function getAvailableTypes(): array
    {
        return array_map(
            static fn (DossierTypeConfigInterface $config) => $config->getDossierType(),
            $this->dossierTypeManager->getAvailableConfigs()
        );
    }

    /**
     * @return DossierType[]
     */
    public function getAvailableTypesOrderedByName(): array
    {
        $types = $this->getAvailableTypes();

        usort(
            $types,
            fn (DossierType $a, DossierType $b): int => strnatcmp($a->trans($this->translator), $b->trans($this->translator))
        );

        return $types;
    }
}
