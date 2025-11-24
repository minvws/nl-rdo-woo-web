<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Search\Index\Updater;

use Mockery\MockInterface;
use Shared\Domain\Department\Department;
use Shared\Domain\Search\Index\Updater\DepartmentIndexUpdater;
use Shared\Service\Elastic\ElasticClientInterface;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

class DepartmentIndexUpdaterTest extends UnitTestCase
{
    private DepartmentIndexUpdater $indexUpdater;
    private ElasticClientInterface&MockInterface $elasticClient;

    protected function setUp(): void
    {
        $this->elasticClient = \Mockery::mock(ElasticClientInterface::class);

        $this->indexUpdater = new DepartmentIndexUpdater(
            $this->elasticClient,
        );

        parent::setUp();
    }

    public function testUpdateDepartment(): void
    {
        $departmentId = Uuid::v6();
        $department = \Mockery::mock(Department::class);
        $department->shouldReceive('getId')->andReturn($departmentId);
        $department->shouldReceive('getName')->andReturn('Foo Bar');
        $department->shouldReceive('getShortTag')->andReturn('FB');

        $this->elasticClient->expects('updateByQuery')->with(\Mockery::on(
            static fn (array $input) => $input['body']['query']['bool']['should'][0]['match']['departments.id'] === $departmentId
                && $input['body']['script']['params']['department']['name'] === 'FB|Foo Bar'
        ));

        $this->indexUpdater->update($department);
    }
}
