<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Search\Query\Facet\Input;

use RuntimeException;
use Shared\Domain\Search\Query\Facet\Input\DocTypeValue;
use Shared\Tests\Unit\UnitTestCase;

final class DocTypeValueTest extends UnitTestCase
{
    public function testFromStringWithTooManyParts(): void
    {
        self::expectException(RuntimeException::class);
        DocTypeValue::fromString('too.many.parts');
    }
}
