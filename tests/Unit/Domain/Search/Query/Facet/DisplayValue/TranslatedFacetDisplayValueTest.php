<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Search\Query\Facet\DisplayValue;

use Shared\Domain\Search\Query\Facet\DisplayValue\TranslatedFacetDisplayValue;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class TranslatedFacetDisplayValueTest extends UnitTestCase
{
    public function testCreateAndGetters(): void
    {
        $translator = \Mockery::mock(TranslatorInterface::class);

        $facet = TranslatedFacetDisplayValue::fromString('  foo.bar  ');

        $translator
            ->shouldReceive('trans')
            ->with('foo.bar', [], null, null)
            ->andReturn($expectedValue = 'Foo value');

        self::assertEquals($expectedValue, $facet->trans($translator));
    }
}
