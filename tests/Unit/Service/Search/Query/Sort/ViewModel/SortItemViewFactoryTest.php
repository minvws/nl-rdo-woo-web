<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Search\Query\Sort\ViewModel;

use App\Domain\Search\Query\SearchParameters;
use App\Domain\Search\Query\SearchType;
use App\Service\Search\Query\Facet\Input\FacetInputCollection;
use App\Service\Search\Query\Sort\ViewModel\SortItemViewFactory;
use App\Tests\Unit\UnitTestCase;

class SortItemViewFactoryTest extends UnitTestCase
{
    private SortItemViewFactory $sortItems;

    public function setUp(): void
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
