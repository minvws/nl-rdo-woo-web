<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Search\Query\Facet\DisplayValue;

use App\Domain\Search\Query\Facet\DisplayValue\TranslatedFacetDisplayValue;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class TranslatedFacetDisplayValueTest extends MockeryTestCase
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
