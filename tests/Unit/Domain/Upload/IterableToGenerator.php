<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Upload;

trait IterableToGenerator
{
    /**
     * @template K of array-key
     * @template T
     *
     * @param iterable<K,T> $values
     *
     * @return \Generator<K,T>
     */
    private function iterableToGenerator(iterable $values): \Generator
    {
        yield from $values;
    }
}
