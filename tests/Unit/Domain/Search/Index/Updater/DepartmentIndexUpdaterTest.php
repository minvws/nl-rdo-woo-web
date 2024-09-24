<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Search\Index\Updater;

use App\Domain\Search\Index\Updater\DepartmentIndexUpdater;
use App\Entity\Department;
use App\Service\Elastic\ElasticClientInterface;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\Uid\Uuid;

class DepartmentIndexUpdaterTest extends MockeryTestCase
{
    private DepartmentIndexUpdater $indexUpdater;
    private ElasticClientInterface&MockInterface $elasticClient;

    public function setUp(): void
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
            static function (array $input) use ($departmentId) {
                return $input['body']['query']['bool']['should'][0]['match']['departments.id'] === $departmentId
                    && $input['body']['script']['params']['department']['name'] === 'FB|Foo Bar';
            }
        ));

        $this->indexUpdater->update($department);
    }
}
