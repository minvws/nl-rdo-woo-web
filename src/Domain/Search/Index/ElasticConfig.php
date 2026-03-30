<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Index;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

readonly class ElasticConfig
{
    public function __construct(
        #[Autowire(param: 'es_index_prefix')]
        public string $indexPrefix,
        #[Autowire(param: 'es_read_index')]
        public string $readIndex,
        #[Autowire(param: 'es_write_index')]
        public string $writeIndex,
        #[Autowire(param: 'es_suggestions_search_input')]
        public string $suggestionsSearchInput,
    ) {
    }
}
