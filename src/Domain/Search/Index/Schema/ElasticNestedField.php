<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Index\Schema;

enum ElasticNestedField: string
{
    case DOSSIERS = 'dossiers';
    case PAGES = 'pages';
}
