<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service\Search\Query\Definition;

use App\Domain\Search\Query\SearchParametersFactory;
use App\Entity\Department;
use App\Service\Search\Query\Definition\BrowseDepartmentAggregationsQueryDefinition;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[Group('search')]
final class BrowseDepartmentAggregationsQueryDefinitionTest extends KernelTestCase
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
