<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\InvestigationReport;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\ArrayPaginator;
use ApiPlatform\State\ProviderInterface;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Organisation\OrganisationRepository;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportRepository;
use Shared\Service\ApiPlatformService;

use function count;

final readonly class InvestigationReportProvider implements ProviderInterface
{
    public function __construct(
        private OrganisationRepository $organisationRepository,
        private InvestigationReportRepository $investigationReportRepository,
        private int $itemsPerPage,
    ) {
    }

    /**
     * @param array<array-key,string> $uriVariables
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ArrayPaginator|InvestigationReportDto|null
    {
        $organisation = $this->organisationRepository->find($uriVariables['organisationId']);
        if ($organisation === null) {
            return null;
        }

        if ($operation instanceof CollectionOperationInterface) {
            return $this->provideCollection($organisation, $context);
        }

        return $this->provideSingle($organisation, $uriVariables['investigationReportExternalId']);
    }

    /**
     * @param array<array-key,mixed> $context
     */
    private function provideCollection(Organisation $organisation, array $context): ArrayPaginator
    {
        $investigationReports = $this->investigationReportRepository->getByOrganisationAndContainsExternalId(
            $organisation,
            $this->itemsPerPage,
            ApiPlatformService::getCursorFromContext($context),
        );

        return new ArrayPaginator(InvestigationReportMapper::fromEntities($investigationReports), 0, count($investigationReports));
    }

    private function provideSingle(Organisation $organisation, string $investigationReportExternalId): ?InvestigationReportDto
    {
        $investigationReport = $this->investigationReportRepository->findByOrganisationAndExternalId($organisation, $investigationReportExternalId);
        if ($investigationReport === null) {
            return null;
        }

        return InvestigationReportMapper::fromEntity($investigationReport);
    }
}
