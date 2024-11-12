<?php

declare(strict_types=1);

namespace App\Api\Admin\Publication\Search;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Domain\Publication\Dossier\Admin\DossierSearchService;
use Symfony\Component\HttpFoundation\Request;

/**
 * @implements ProviderInterface<SearchResultDto>
 */
class SearchProvider implements ProviderInterface
{
    public function __construct(
        private DossierSearchService $dossierSearchService,
        private SearchResultDtoFactory $searchResultDtoFactory,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        unset($operation);
        unset($uriVariables);

        $searchTerm = $this->getSearchTerm($context);
        if ($searchTerm === null) {
            return null;
        }

        return $this->searchResultDtoFactory->makeCollection([
            ...$this->dossierSearchService->searchDossiers($searchTerm),
            ...$this->dossierSearchService->searchDocuments($searchTerm),
            ...$this->dossierSearchService->searchMainDocuments($searchTerm),
            ...$this->dossierSearchService->searchAttachments($searchTerm),
        ]);
    }

    /**
     * @param array<string,mixed> $context
     */
    private function getSearchTerm(array $context): ?string
    {
        /** @var ?Request $request */
        $request = $context['request'] ?? null;
        if ($request === null) {
            return null;
        }

        $searchTerm = trim($request->query->getString('q'));

        return $searchTerm === ''
            ? null
            : $searchTerm;
    }
}
