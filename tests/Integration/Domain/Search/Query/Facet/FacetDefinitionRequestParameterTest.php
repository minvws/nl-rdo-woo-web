<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Domain\Search\Query\Facet;

use PHPUnit\Framework\Attributes\DataProvider;
use Shared\Domain\Search\Query\Facet\FacetDefinitions;
use Shared\Service\Search\Model\FacetKey;
use Shared\Tests\Integration\SharedWebTestCase;

final class FacetDefinitionRequestParameterTest extends SharedWebTestCase
{
    #[DataProvider('facetKeyProvider')]
    public function testRequestParameterMatchesDeprecatedParamName(FacetKey $facetKey): void
    {
        self::bootKernel();

        $facetDefinitions = self::fromContainer(FacetDefinitions::class);

        /*
         * @phpstan-ignore method.deprecated (Comparing against the deprecated implementation is the entire point of this test)
         */
        $deprecatedParamName = $facetKey->getParamName();

        self::assertSame(
            $deprecatedParamName,
            $facetDefinitions->get($facetKey)->getRequestParameter(),
        );
    }

    /**
     * @return iterable<string, array{FacetKey}>
     */
    public static function facetKeyProvider(): iterable
    {
        foreach (FacetKey::cases() as $facetKey) {
            yield $facetKey->value => [$facetKey];
        }
    }
}
