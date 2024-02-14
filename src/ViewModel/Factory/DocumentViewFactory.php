<?php

declare(strict_types=1);

namespace App\ViewModel\Factory;

use App\Entity\Document as DocumentEntity;
use App\Service\Search\SearchService;
use App\ViewModel\Document;

final readonly class DocumentViewFactory
{
    public function __construct(
        private SearchService $searchService,
    ) {
    }

    public function getDocumentViewModel(DocumentEntity $documentEntity): Document
    {
        return new Document(
            ingested: $this->searchService->isIngested($documentEntity),
            entity: $documentEntity,
        );
    }
}
