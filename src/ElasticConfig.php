<?php

declare(strict_types=1);

namespace App;

class ElasticConfig
{
    // The elasticsearch index we are using.
    public const INDEX_PREFIX = 'woopie-';

    // Aliases
    public const READ_INDEX = 'woopie-read';
    public const WRITE_INDEX = 'woopie-write';

    // Suggestion set names
    public const SUGGESTIONS_SEARCH_INPUT = 'search-input';
}
