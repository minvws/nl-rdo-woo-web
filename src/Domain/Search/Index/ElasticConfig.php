<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Index;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

readonly class ElasticConfig
{
    public function __construct(
        #[Autowire(param: 'elasticsearch.index.prefix')]
        public string $indexPrefix,
        #[Autowire(param: 'elasticsearch.index.read')]
        public string $readIndex,
        #[Autowire(param: 'elasticsearch.index.write')]
        public string $writeIndex,
        #[Autowire(param: 'elasticsearch.suggestions_search_input')]
        public string $suggestionsSearchInput,
        #[Autowire(param: 'elasticsearch.index.worker_stats')]
        public string $workerStatsIndex,
    ) {
    }
}
