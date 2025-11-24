<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Index;

class ElasticConfig
{
    // The elasticsearch index we are using.
    public const string INDEX_PREFIX = 'woopie-';

    // Aliases
    public const string READ_INDEX = 'woopie-read';
    public const string WRITE_INDEX = 'woopie-write';

    // Suggestion set names
    public const string SUGGESTIONS_SEARCH_INPUT = 'search-input';
}
