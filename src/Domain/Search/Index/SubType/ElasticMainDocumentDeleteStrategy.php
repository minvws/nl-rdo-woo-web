<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Index\SubType;

use Shared\Domain\Publication\MainDocument\AbstractMainDocument;
use Shared\Domain\Publication\MainDocument\MainDocumentDeleteStrategyInterface;
use Shared\Domain\Search\Index\ElasticDocumentId;
use Shared\Domain\Search\SearchDispatcher;

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
