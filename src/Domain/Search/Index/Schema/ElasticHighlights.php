<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Index\Schema;

use function sprintf;

readonly class ElasticHighlights
{
    /**
     * @return list<string>
     */
    public static function getPaths(): array
    {
        return [
            self::highlightPath(ElasticPath::pagesContent()),
            self::highlightPath(ElasticPath::dossiersTitle()),
            self::highlightPath(ElasticPath::dossiersSummary()),
        ];
    }

    /**
     * @return list<string>
     */
    public static function getTopLevelPaths(): array
    {
        return [
            self::highlightPath(ElasticPath::title()),
            self::highlightPath(ElasticPath::summary()),
        ];
    }

    /**
     * @return list<string>
     */
    public static function getContentTreePaths(): array
    {
        return [
            self::highlightPath(ElasticPath::contentTreeTitle()),
            self::highlightPath(ElasticPath::contentTreeBody()),
        ];
    }

    public static function highlightPath(ElasticPath $path): string
    {
        return sprintf('[highlight][%s]', $path->value);
    }
}
