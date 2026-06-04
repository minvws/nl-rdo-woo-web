<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Service\Search\Query\Definition;

use Mockery;
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
        $department = Mockery::mock(Department::class);
        $department->expects('getShortTag')->andReturn('foo');
        $department->expects('getName')->andReturn('bar');

        $searchParametersFactory = self::fromContainer(SearchParametersFactory::class);
        $searchParameters = $searchParametersFactory->createForDepartment($department);

        $this->matchDefinitionToSnapshot(BrowseDepartmentAggregationsQueryDefinition::class, $searchParameters);
    }
}
