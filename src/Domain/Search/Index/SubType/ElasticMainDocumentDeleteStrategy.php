<?php

declare(strict_types=1);

namespace App\Domain\Search\Index\SubType;

use App\Domain\Publication\MainDocument\AbstractMainDocument;
use App\Domain\Publication\MainDocument\MainDocumentDeleteStrategyInterface;
use App\Domain\Search\Index\ElasticDocumentId;
use App\Domain\Search\SearchDispatcher;

readonly class ElasticMainDocumentDeleteStrategy implements MainDocumentDeleteStrategyInterface
{
    public function __construct(
        private SearchDispatcher $searchDispatcher,
    ) {
    }

    public function delete(AbstractMainDocument $mainDocument): void
    {
        $this->searchDispatcher->dispatchDeleteElasticDocumentCommand(
            ElasticDocumentId::forObject($mainDocument),
        );
    }
}
