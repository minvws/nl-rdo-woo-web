<?php

declare(strict_types=1);

namespace App\Api\Admin\Publication\Search;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Api\ContextGetter;
use App\Domain\Publication\Attachment\Entity\AbstractAttachment;
use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Admin\DossierSearchService;
use App\Domain\Publication\Dossier\Admin\SearchParameters;
use App\Domain\Publication\Dossier\Type\DossierType;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Publication\MainDocument\AbstractMainDocument;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProviderInterface<SearchResultDto>
 */
class SearchProvider implements ProviderInterface
{
    use ContextGetter;

    public function __construct(
        private DossierSearchService $dossierSearchService,
        private SearchResultDtoFactory $searchResultDtoFactory,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        unset($operation);
        unset($uriVariables);

        $searchParams = $this->buildSearchParameters($this->getRequest($context)->attributes);

        $entities = $searchParams->resultType === null
            ? $this->searchAll($searchParams)
            : $this->searchResult($searchParams->resultType, $searchParams);

        return $this->searchResultDtoFactory
            ->makeCollection(iterator_to_array($entities, preserve_keys: false));
    }

    /**
     * @return list<AbstractDossier|Document|AbstractMainDocument|AbstractAttachment>
     */
    private function searchResult(SearchResultType $resultType, SearchParameters $searchParameters): array
    {
        return match ($resultType) {
            SearchResultType::DOSSIER => $this->dossierSearchService->searchDossiers($searchParameters),
            SearchResultType::MAIN_DOCUMENT => $this->dossierSearchService->searchMainDocuments($searchParameters),
            SearchResultType::ATTACHMENT => $this->dossierSearchService->searchAttachments($searchParameters),
            SearchResultType::DOCUMENT => $this->dossierSearchService->searchDocuments($searchParameters),
        };
    }

    /**
     * @return \Generator<int,AbstractDossier|Document|AbstractMainDocument|AbstractAttachment>
     */
    private function searchAll(SearchParameters $searchParameters): \Generator
    {
        foreach (SearchResultType::cases() as $searchResultType) {
            yield from $this->searchResult($searchResultType, $searchParameters);
        }
    }

    private function buildSearchParameters(ParameterBag $attributes): SearchParameters
    {
        $searchTerm = $attributes->getString('searchQuery');

        /** @var ?DossierType $dossierType */
        $dossierType = $attributes->get('publicationTypeQuery');

        /** @var ?Uuid $dossierId */
        $dossierId = $attributes->get('dossierIdQuery');

        /** @var ?SearchResultType $resultType */
        $resultType = $attributes->get('resultTypeQuery');

        return new SearchParameters(
            searchTerm: $searchTerm,
            dossierType: $dossierType,
            dossierId: $dossierId,
            resultType: $resultType,
        );
    }
}
