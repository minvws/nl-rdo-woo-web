<?php

declare(strict_types=1);

namespace Shared\Api\Admin\Publication\Search;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Shared\Api\ContextGetter;
use Shared\Domain\Publication\Dossier\DossierRepository;
use Shared\Domain\Publication\Dossier\Type\DossierType;
use Shared\Domain\Search\Index\Dossier\Mapper\PrefixedDossierNr;
use Shared\Domain\Search\Query\SearchParameters;
use Shared\Domain\Search\Query\SearchParametersFactory;
use Shared\Service\Search\Query\Definition\AdminDossiersAndDocumentsQueryDefinition;
use Shared\Service\Search\SearchService;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProviderInterface<SearchResultDto>
 */
readonly class SearchProvider implements ProviderInterface
{
    use ContextGetter;

    public function __construct(
        private SearchResultDtoFactory $searchResultDtoFactory,
        private AdminDossiersAndDocumentsQueryDefinition $queryDefinition,
        private SearchService $searchService,
        private SearchParametersFactory $searchParametersFactory,
        private DossierRepository $dossierRepository,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        unset($operation);
        unset($uriVariables);

        $searchParameters = $this->buildSearchParameters($this->getRequest($context)->attributes);

        $results = $this->searchService->getResult($this->queryDefinition, $searchParameters)->getEntries();

        return $this->searchResultDtoFactory->makeCollection($results);
    }

    private function buildSearchParameters(ParameterBag $attributes): SearchParameters
    {
        $searchTerm = $attributes->getString('searchQuery');

        /** @var ?DossierType $dossierType */
        $dossierType = $attributes->get('publicationTypeQuery');

        /** @var ?Uuid $dossierId */
        $dossierId = $attributes->get('dossierIdQuery');
        $dossierNr = null;
        if ($dossierId !== null) {
            $dossier = $this->dossierRepository->findOneByDossierId($dossierId);
            $dossierNr = PrefixedDossierNr::forDossier($dossier);
        }

        /** @var ?SearchResultType $resultType */
        $resultType = $attributes->get('resultTypeQuery');

        return $this->searchParametersFactory->forAdminSearch(
            searchTerm: $searchTerm,
            dossierType: $dossierType,
            dossierNr: $dossierNr,
            resultType: $resultType,
        );
    }
}
