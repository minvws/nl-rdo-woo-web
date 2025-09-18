<?php

declare(strict_types=1);

namespace App\Tests\Unit\Api\OpenApi;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use App\Api\OpenApi\PathItemIterator;
use App\Tests\Unit\UnitTestCase;

final class PathItemIteratorTest extends UnitTestCase
{
    public function testFromOnEmptyPathItem(): void
    {
        $pathItem = new PathItem();

        $iterator = PathItemIterator::from($pathItem);

        $result = iterator_to_array($iterator);

        $this->assertSame([], $result);
    }

    public function testFrom(): void
    {
        /** @var Operation $getOperation */
        $getOperation = \Mockery::mock(Operation::class);

        /** @var Operation $postOperation */
        $postOperation = \Mockery::mock(Operation::class);

        $pathItem = new PathItem(
            get: $getOperation,
            post: $postOperation,
        );

        $iterator = PathItemIterator::from($pathItem);

        $result = iterator_to_array($iterator);

        $this->assertSame([
            'GET' => $getOperation,
            'POST' => $postOperation,
        ], $result);
    }

    public function testFromItWillSkipUnknownMethods(): void
    {
        /** @var Operation $getOperation */
        $getOperation = \Mockery::mock(Operation::class);

        /** @var Operation $putOperation */
        $putOperation = \Mockery::mock(Operation::class);

        $pathItem = new PathItem(
            get: $getOperation,
            put: $putOperation,
        );

        $pathItem::$methods = ['GET', 'PUT', 'UNKNOWN'];

        $iterator = PathItemIterator::from($pathItem);

        $result = iterator_to_array($iterator);

        $this->assertSame([
            'GET' => $getOperation,
            'PUT' => $putOperation,
        ], $result);
    }
}
