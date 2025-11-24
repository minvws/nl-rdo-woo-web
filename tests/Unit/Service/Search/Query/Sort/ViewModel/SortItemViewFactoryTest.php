<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Search\Query\Sort\ViewModel;

use Shared\Domain\Search\Query\Facet\Input\FacetInputCollection;
use Shared\Domain\Search\Query\SearchParameters;
use Shared\Domain\Search\Query\SearchType;
use Shared\Service\Search\Query\Sort\ViewModel\SortItemViewFactory;
use Shared\Tests\Unit\UnitTestCase;

class SortItemViewFactoryTest extends UnitTestCase
{
    private SortItemViewFactory $sortItems;

    protected function setUp(): void
    {
        $this->sortItems = new SortItemViewFactory();

        parent::setUp();
    }

    public function testMakeForDefaultSearch(): void
    {
        $searchParameters = new SearchParameters(
            new FacetInputCollection(),
        );

        $this->assertMatchesObjectSnapshot(
            $this->sortItems->make($searchParameters)
        );
    }

    public function testMakeForDossierSearch(): void
    {
        $searchParameters = new SearchParameters(
            new FacetInputCollection(),
            searchType: SearchType::DOSSIER,
        );

        $this->assertMatchesObjectSnapshot(
            $this->sortItems->make($searchParameters)
        );
    }
}
