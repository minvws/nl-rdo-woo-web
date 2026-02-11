<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Utils;

use Carbon\Carbon;
use DateTimeImmutable;
use Shared\Service\Utils\CastTypes;
use Shared\Tests\Unit\UnitTestCase;
use Webmozart\Assert\Assert;

class CastTypesTest extends UnitTestCase
{
    public function testAsImmutableDateWithFormat(): void
    {
        $result = CastTypes::asImmutableDate('1-1-2000 00:00:00', 'd-m-Y H:i:s');
        Assert::isInstanceOf($result, DateTimeImmutable::class);

        self::assertTrue(Carbon::createStrict(2000)->eq($result));
    }

    public function testAsImmutableDateWithoutFormat(): void
    {
        $result = CastTypes::asImmutableDate('1-1-2000');
        Assert::isInstanceOf($result, DateTimeImmutable::class);

        self::assertTrue(Carbon::createStrict(2000)->eq($result));
    }

    public function testAsImmutableDateWithInvalidFormat(): void
    {
        $result = CastTypes::asImmutableDate('invalid');

        self::assertNull($result);
    }
}
