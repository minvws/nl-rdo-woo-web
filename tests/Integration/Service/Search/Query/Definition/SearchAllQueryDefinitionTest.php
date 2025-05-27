<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service\Search\Query\Definition;

use App\Domain\Search\Query\Facet\Input\FacetInputFactory;
use App\Domain\Search\Query\SearchParameters;
use App\Service\Search\Query\Definition\SearchAllQueryDefinition;
use App\Service\Search\Query\Sort\SortField;
use App\Service\Search\Query\Sort\SortOrder;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[Group('search')]
final class SearchAllQueryDefinitionTest extends KernelTestCase
{
    use QueryDefinitionTestTrait;

    public function testElasticQueryBuiltFromDefinition(): void
    {
        $facetInputFactory = self::getContainer()->get(FacetInputFactory::class);

        $searchParameters = new SearchParameters(
            $facetInputFactory->create(),
            limit: 10,
            offset: 20,
            query: 'foo',
            sortField: SortField::DECISION_DATE,
            sortOrder: SortOrder::DESC,
        );

        $this->matchDefinitionToSnapshot(SearchAllQueryDefinition::class, $searchParameters);
    }
}
