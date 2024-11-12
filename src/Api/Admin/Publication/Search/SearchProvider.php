<?php

declare(strict_types=1);

namespace App\Api\Admin\Publication\Search;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Api\ContextGetter;
use App\Domain\Publication\Dossier\Admin\DossierSearchService;
use App\Domain\Publication\Dossier\Type\DossierType;
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

        $attributes = $this
            ->getRequest($context)
            ->attributes;

        $searchTerm = $attributes->getString('search_query');

        /** @var ?DossierType $dossierType */
        $dossierType = $attributes->get('type_query');

        /** @var ?Uuid $dossierId */
        $dossierId = $attributes->get('dossier_id_query');

        $entities = [
            ...$this->dossierSearchService->searchDossiers(
                $searchTerm,
                dossierId: $dossierId,
                dossierType: $dossierType,
            ),
            ...$this->dossierSearchService->searchMainDocuments(
                $searchTerm,
                dossierId: $dossierId,
                dossierType: $dossierType
            ),
            ...$this->dossierSearchService->searchAttachments(
                $searchTerm,
                dossierId: $dossierId,
                dossierType: $dossierType
            ),
        ];

        if ($dossierType === null || $dossierType->isWooDecision()) {
            $entities = [
                ...$entities,
                ...$this->dossierSearchService->searchDocuments(
                    $searchTerm,
                    dossierId: $dossierId,
                ),
            ];
        }

        return $this->searchResultDtoFactory->makeCollection($entities);
    }
}
