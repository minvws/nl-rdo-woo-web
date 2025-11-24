<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision\Document\ViewModel;

use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document as DocumentEntity;
use Shared\Service\Search\SearchService;

final readonly class DocumentViewFactory
{
    public function __construct(
        private SearchService $searchService,
    ) {
    }

    public function make(DocumentEntity $documentEntity): Document
    {
        return new Document(
            ingested: $this->searchService->isIngested($documentEntity),
            entity: $documentEntity,
        );
    }
}
