<?php

declare(strict_types=1);

namespace Shared\Api\Admin\Publication\Search;

enum SearchResultType: string
{
    case DOSSIER = 'dossier';
    case DOCUMENT = 'document';
    case MAIN_DOCUMENT = 'main_document';
    case ATTACHMENT = 'attachment';

    /**
     * @return list<string>
     */
    public static function getAllValues(): array
    {
        return array_map(fn (SearchResultType $type): string => $type->value, SearchResultType::cases());
    }
}
