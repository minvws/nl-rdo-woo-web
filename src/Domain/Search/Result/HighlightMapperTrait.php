<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Result;

use MinVWS\TypeArray\TypeArray;

use function array_merge;

trait HighlightMapperTrait
{
    /**
     * @param array<array-key, string> $paths
     *
     * @return array<array-key, string>
     */
    protected function getHighlightData(TypeArray $hit, array $paths): array
    {
        $highlightData = [];
        foreach ($paths as $path) {
            if ($hit->exists($path)) {
                $highlightData = array_merge($highlightData, $hit->getTypeArray($path)->toArray());
            }
        }

        /** @var array<string> $highlightData */
        return $highlightData;
    }
}
