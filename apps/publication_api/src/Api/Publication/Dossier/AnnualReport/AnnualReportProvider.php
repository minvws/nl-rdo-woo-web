<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\AnnualReport;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\ArrayPaginator;
use ApiPlatform\State\ProviderInterface;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Organisation\OrganisationRepository;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportRepository;
use Shared\Service\ApiPlatformService;

use function count;

final readonly class AnnualReportProvider implements ProviderInterface
{
    public function __construct(
        private OrganisationRepository $organisationRepository,
        private AnnualReportRepository $annualReportRepository,
        private int $itemsPerPage,
    ) {
    }

    /**
     * @param array<array-key,string> $uriVariables
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ArrayPaginator|AnnualReportDto|null
    {
        $organisation = $this->organisationRepository->find($uriVariables['organisationId']);
        if ($organisation === null) {
            return null;
        }

        if ($operation instanceof CollectionOperationInterface) {
            return $this->provideCollection($organisation, $context);
        }

        return $this->provideSingle($organisation, $uriVariables['annualReportExternalId']);
    }

    /**
     * @param array<array-key,mixed> $context
     */
    private function provideCollection(Organisation $organisation, array $context): ArrayPaginator
    {
        $annualReport = $this->annualReportRepository->getByOrganisationAndContainsExternalId(
            $organisation,
            $this->itemsPerPage,
            ApiPlatformService::getCursorFromContext($context),
        );

        return new ArrayPaginator(AnnualReportMapper::fromEntities($annualReport), 0, count($annualReport));
    }

    private function provideSingle(Organisation $organisation, string $annualReportExternalId): ?AnnualReportDto
    {
        $annualReport = $this->annualReportRepository->findByOrganisationAndExternalId($organisation, $annualReportExternalId);
        if ($annualReport === null) {
            return null;
        }

        return AnnualReportMapper::fromEntity($annualReport);
    }
}
