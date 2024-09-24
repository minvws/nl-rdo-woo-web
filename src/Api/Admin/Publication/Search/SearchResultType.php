<?php

declare(strict_types=1);

namespace App\Api\Admin\Publication\Search;

enum SearchResultType: string
{
    case DOSSIER = 'dossier';
    case DOCUMENT = 'document';
}
