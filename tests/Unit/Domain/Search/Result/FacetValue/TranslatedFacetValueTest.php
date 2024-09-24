<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Search\Result\FacetValue;

use App\Domain\Search\Result\FacetValue\TranslatedFacetValue;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class TranslatedFacetValueTest extends MockeryTestCase
{
    public function testCreateAndGetters(): void
    {
        $translator = \Mockery::mock(TranslatorInterface::class);

        $facet = TranslatedFacetValue::create($translator, 'foo', 'bar');

        $translator
            ->expects('trans')
            ->twice()
            ->with('public.documents.foo.bar')
            ->andReturn($expectedValue = 'Foo value');

        $translator
            ->expects('trans')
            ->with('public.search.type_description.bar')
            ->andReturn($expectedDescription = 'Foo description');

        self::assertEquals($expectedValue, $facet->getValue());
        self::assertEquals($expectedValue, strval($facet));
        self::assertEquals($expectedDescription, $facet->getDescription());
    }
}
