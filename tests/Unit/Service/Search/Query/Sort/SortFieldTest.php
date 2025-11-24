<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Search\Query\Sort;

use Shared\Service\Search\Query\Sort\SortField;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class SortFieldTest extends UnitTestCase
{
    public function testTrans(): void
    {
        $locale = 'en_GB';
        $translator = \Mockery::mock(TranslatorInterface::class);
        $translator
            ->expects('trans')
            ->with('global.' . SortField::PUBLICATION_DATE->value, [], null, $locale)
            ->andReturn('foo');

        self::assertEquals(
            'foo',
            SortField::PUBLICATION_DATE->trans($translator, $locale),
        );
    }
}
