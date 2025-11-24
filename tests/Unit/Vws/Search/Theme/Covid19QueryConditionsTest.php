<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Vws\Search\Theme;

use Doctrine\Common\Collections\ArrayCollection;
use Erichard\ElasticQueryBuilder\Query\BoolQuery;
use Mockery\MockInterface;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Organisation\OrganisationRepository;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Domain\Search\Query\Facet\FacetList;
use Shared\Domain\Search\Query\SearchParameters;
use Shared\Tests\Unit\UnitTestCase;
use Shared\Vws\Search\Theme\Covid19QueryConditionBuilder;
use Symfony\Component\Uid\Uuid;

class Covid19QueryConditionsTest extends UnitTestCase
{
    private OrganisationRepository&MockInterface $organisationRepository;
    private Covid19QueryConditionBuilder $conditions;

    protected function setUp(): void
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
