<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Search\Query\Sort;

use Shared\Service\Search\Query\Sort\SortOrder;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class SortOrderTest extends UnitTestCase
{
    public function testTrans(): void
    {
        $locale = 'en_GB';
        $translator = \Mockery::mock(TranslatorInterface::class);
        $translator
            ->expects('trans')
            ->with('global.sort.' . SortOrder::DESC->value, [], null, $locale)
            ->andReturn('foo');

        self::assertEquals(
            'foo',
            SortOrder::DESC->trans($translator, $locale),
        );
    }
}
