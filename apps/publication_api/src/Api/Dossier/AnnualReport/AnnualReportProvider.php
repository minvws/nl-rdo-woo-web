<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\AnnualReport;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\ArrayPaginator;
use ApiPlatform\State\ProviderInterface;
use PublicationApi\Api\ExternalIdFactory;
use PublicationApi\Api\Organisation\OrganisationResolver;
use PublicationApi\Domain\Exception\EntityNotFoundException;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportRepository;
use Shared\Service\ApiPlatformService;
use Shared\ValueObject\ExternalId;

use function count;

final readonly class AnnualReportProvider implements ProviderInterface
{
    public function __construct(
        private OrganisationResolver $organisationResolver,
        private AnnualReportRepository $annualReportRepository,
        private AnnualReportMapper $annualReportMapper,
        private int $itemsPerPage,
    ) {
    }

    /**
     * @param array<array-key,string> $uriVariables
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ArrayPaginator|AnnualReportResponseDto
    {
        $organisation = $this->organisationResolver->resolve($uriVariables);

        if ($operation instanceof CollectionOperationInterface) {
            return $this->provideCollection($organisation, $context);
        }

        return $this->provideSingle($organisation, ExternalIdFactory::create($uriVariables['dossierExternalId']));
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

        return new ArrayPaginator($this->annualReportMapper->fromEntities($annualReport), 0, count($annualReport));
    }

    private function provideSingle(Organisation $organisation, ExternalId $dossierExternalId): AnnualReportResponseDto
    {
        $annualReport = $this->annualReportRepository->findByOrganisationAndExternalId($organisation, $dossierExternalId);
        if ($annualReport === null) {
            throw EntityNotFoundException::for('AnnualReport', $dossierExternalId);
        }

        return $this->annualReportMapper->fromEntity($annualReport);
    }
}
