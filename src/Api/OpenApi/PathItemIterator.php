<?php

declare(strict_types=1);

namespace App\Api\OpenApi;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;

final readonly class PathItemIterator implements \IteratorAggregate
{
    private function __construct(private PathItem $pathItem)
    {
    }

    public static function from(PathItem $pathItem): self
    {
        return new self($pathItem);
    }

    /**
     * @return \Generator<string,Operation>
     */
    public function getIterator(): \Generator
    {
        /** @var list<string> $methods */
        $methods = PathItem::$methods;

        foreach ($methods as $method) {
            $methodName = sprintf('get%s', strtolower(ucfirst($method)));

            if (! method_exists($this->pathItem, $methodName)) {
                continue;
            }

            /** @var ?Operation $operation */
            $operation = $this->pathItem->{$methodName}();
            if ($operation !== null) {
                yield $method => $operation;
            }
        }
    }
}
