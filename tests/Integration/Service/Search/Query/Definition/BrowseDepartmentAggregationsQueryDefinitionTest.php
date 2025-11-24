<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Service\Search\Query\Definition;

use PHPUnit\Framework\Attributes\Group;
use Shared\Domain\Department\Department;
use Shared\Domain\Search\Query\SearchParametersFactory;
use Shared\Service\Search\Query\Definition\BrowseDepartmentAggregationsQueryDefinition;
use Shared\Tests\Integration\SharedWebTestCase;

#[Group('search')]
final class BrowseDepartmentAggregationsQueryDefinitionTest extends SharedWebTestCase
{
    use QueryDefinitionTestTrait;

    public function testElasticQueryBuiltFromDefinition(): void
    {
        $department = \Mockery::mock(Department::class);
        $department->shouldReceive('getShortTag')->andReturn('foo');
        $department->shouldReceive('getName')->andReturn('bar');

        /** @var SearchParametersFactory $searchParametersFactory */
        $searchParametersFactory = self::getContainer()->get(SearchParametersFactory::class);
        $searchParameters = $searchParametersFactory->createForDepartment($department);

        $this->matchDefinitionToSnapshot(BrowseDepartmentAggregationsQueryDefinition::class, $searchParameters);
    }
}
