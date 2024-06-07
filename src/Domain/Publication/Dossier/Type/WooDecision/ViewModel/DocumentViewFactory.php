<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\ViewModel;

use App\Entity\Document as DocumentEntity;
use App\Service\Search\SearchService;

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
