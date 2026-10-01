<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Search\Index\Updater;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Department\Department;
use Shared\Domain\Search\Index\Updater\DepartmentIndexUpdater;
use Shared\Service\Elastic\ElasticClientInterface;
use Shared\Tests\ElasticConfigFactory;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

class DepartmentIndexUpdaterTest extends UnitTestCase
{
    private DepartmentIndexUpdater $indexUpdater;
    private ElasticClientInterface&MockInterface $elasticClient;

    protected function setUp(): void
    {
        $this->elasticClient = Mockery::mock(ElasticClientInterface::class);

        $this->indexUpdater = new DepartmentIndexUpdater(
            $this->elasticClient,
            ElasticConfigFactory::default(),
        );

        parent::setUp();
    }

    public function testUpdateDepartment(): void
    {
        $departmentId = Uuid::v6();
        $department = Mockery::mock(Department::class);
        $department->expects('getId')->times(3)->andReturn($departmentId);
        $department->expects('getName')->andReturn('Foo Bar');
        $department->expects('getShortTag')->andReturn('FB');

        $this->elasticClient->expects('updateByQuery')->with(Mockery::capture($input));

        $this->indexUpdater->update($department);

        Assert::isArray($input);
        $body = $input['body'];
        Assert::isArray($body);
        $query = $body['query'];
        Assert::isArray($query);
        $script = $body['script'];
        Assert::isArray($script);

        self::assertEquals([
            'should' => [
                ['match' => ['departments.id' => $departmentId]],
                ['nested' => [
                    'path' => 'dossiers',
                    'query' => ['term' => ['dossiers.departments.id' => $departmentId]],
                ]],
            ],
            'minimum_should_match' => 1,
        ], $query['bool']);

        self::assertEquals(
            ['department' => ['name' => 'FB|Foo Bar', 'id' => $departmentId]],
            $script['params'],
        );
    }
}
