<?php

declare(strict_types=1);

namespace Shared\Domain\Ingest\Content;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

use function count;
use function implode;

use const PHP_EOL;

class ContentExtractCollection implements IteratorAggregate
{
    /**
     * @var ContentExtract[]
     */
    private array $extracts = [];

    private bool $failure = false;

    public function append(ContentExtract $extract): void
    {
        $this->extracts[] = $extract;
    }

    public function markAsFailure(): self
    {
        $this->failure = true;

        return $this;
    }

    public function isFailure(): bool
    {
        return $this->failure;
    }

    public function getCombinedContent(): string
    {
        $content = [];
        foreach ($this->extracts as $extracts) {
            $content[] = $extracts->content;
        }

        return implode(PHP_EOL, $content);
    }

    public function isEmpty(): bool
    {
        return count($this->extracts) === 0;
    }

    /**
     * @return Traversable<ContentExtract>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->extracts);
    }
}
