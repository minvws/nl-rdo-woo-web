<?php

declare(strict_types=1);

namespace App\Domain\Search\Index\Schema;

readonly class ElasticHighlights
{
    /**
     * @return list<string>
     */
    public static function getPaths(): array
    {
        return [
            self::highlightPath(
                ElasticPath::pagesContent(),
            ),
            self::highlightPath(
                ElasticPath::dossiersTitle(),
            ),
            self::highlightPath(
                ElasticPath::dossiersSummary(),
            ),
        ];
    }

    public static function highlightPath(ElasticPath $path): string
    {
        return sprintf('[highlight][%s]', $path->value);
    }
}
