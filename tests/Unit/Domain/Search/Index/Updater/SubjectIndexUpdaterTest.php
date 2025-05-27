<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Search\Index\Updater;

use App\Domain\Publication\Subject\Subject;
use App\Domain\Search\Index\Updater\SubjectIndexUpdater;
use App\Service\Elastic\ElasticClientInterface;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\Uid\Uuid;

class SubjectIndexUpdaterTest extends MockeryTestCase
{
    private SubjectIndexUpdater $indexUpdater;
    private ElasticClientInterface&MockInterface $elasticClient;

    public function setUp(): void
    {
        $this->elasticClient = \Mockery::mock(ElasticClientInterface::class);

        $this->indexUpdater = new SubjectIndexUpdater(
            $this->elasticClient,
        );

        parent::setUp();
    }

    public function testUpdateDepartment(): void
    {
        $subject = \Mockery::mock(Subject::class);
        $subject->shouldReceive('getId')->andReturn($subjectId = Uuid::v6());
        $subject->shouldReceive('getName')->andReturn('Foo Bar');

        $this->elasticClient->expects('updateByQuery')->with(\Mockery::on(
            static fn (array $input) => $input['body']['query']['bool']['should'][0]['match']['subject.id'] === $subjectId
                && $input['body']['script']['params']['subject']['name'] === 'Foo Bar'
        ));

        $this->indexUpdater->update($subject);
    }
}
