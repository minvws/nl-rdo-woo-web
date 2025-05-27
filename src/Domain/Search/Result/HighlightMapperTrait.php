<?php

declare(strict_types=1);

namespace App\Domain\Search\Result;

use MinVWS\TypeArray\TypeArray;

trait HighlightMapperTrait
{
    /**
     * @param string[] $paths
     *
     * @return string[]
     */
    protected function getHighlightData(TypeArray $hit, array $paths): array
    {
        $highlightData = [];
        foreach ($paths as $path) {
            if ($hit->exists($path)) {
                $highlightData = array_merge($highlightData, $hit->getTypeArray($path)->toArray());
            }
        }

        /** @var string[] $highlightData */
        return $highlightData;
    }
}
