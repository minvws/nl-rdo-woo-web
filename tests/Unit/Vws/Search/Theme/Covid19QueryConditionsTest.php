<?php

declare(strict_types=1);

namespace App\Tests\Unit\Vws\Search\Theme;

use App\Domain\Organisation\Organisation;
use App\Domain\Organisation\OrganisationRepository;
use App\Domain\Publication\Subject\Subject;
use App\Domain\Search\Query\Facet\FacetList;
use App\Domain\Search\Query\SearchParameters;
use App\Tests\Unit\UnitTestCase;
use App\Vws\Search\Theme\Covid19QueryConditionBuilder;
use Doctrine\Common\Collections\ArrayCollection;
use Erichard\ElasticQueryBuilder\Query\BoolQuery;
use Mockery\MockInterface;
use Symfony\Component\Uid\Uuid;

class Covid19QueryConditionsTest extends UnitTestCase
{
    private OrganisationRepository&MockInterface $organisationRepository;
    private Covid19QueryConditionBuilder $conditions;

    public function setUp(): void
    {
        $this->organisationRepository = \Mockery::mock(OrganisationRepository::class);

        $this->conditions = new Covid19QueryConditionBuilder(
            $this->organisationRepository,
        );

        parent::setUp();
    }

    public function testApplyToQueryThrowsExceptionWhenOrganisationCannotBeFound(): void
    {
        $facetList = \Mockery::mock(FacetList::class);
        $searchParameters = \Mockery::mock(SearchParameters::class);
        $boolQuery = new BoolQuery();

        $this->organisationRepository
            ->expects('findOneBy')
            ->with(['name' => Covid19QueryConditionBuilder::ORGANISATION])
            ->andReturnNull();

        $this->expectException(\RuntimeException::class);
        $this->conditions->applyToQuery($facetList, $searchParameters, $boolQuery);
    }

    public function testApplyToQuerySuccessful(): void
    {
        $facetList = \Mockery::mock(FacetList::class);
        $searchParameters = \Mockery::mock(SearchParameters::class);
        $boolQuery = new BoolQuery();

        $subjectA = \Mockery::mock(Subject::class);
        $subjectA->expects('getName')->andReturn('RIVM');
        $subjectA->expects('getId')->andReturn(Uuid::fromRfc4122('1ef3ea0e-678d-6cee-9604-c962be9d60b2'));

        $subjectB = \Mockery::mock(Subject::class);
        $subjectB->expects('getName')->andReturn('Foo Bar');

        $organisation = \Mockery::mock(Organisation::class);
        $organisation->expects('getSubjects')->andReturn(new ArrayCollection([$subjectA, $subjectB]));

        $this->organisationRepository
            ->expects('findOneBy')
            ->with(['name' => Covid19QueryConditionBuilder::ORGANISATION])
            ->andReturn($organisation);

        $this->conditions->applyToQuery($facetList, $searchParameters, $boolQuery);

        $this->assertMatchesJsonSnapshot($boolQuery->build());
    }
}
