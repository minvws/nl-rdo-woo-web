<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Result;

use MinVWS\TypeArray\TypeArray;
use Webmozart\Assert\Assert;

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

        Assert::allString($highlightData);

        return $highlightData;
    }
}
