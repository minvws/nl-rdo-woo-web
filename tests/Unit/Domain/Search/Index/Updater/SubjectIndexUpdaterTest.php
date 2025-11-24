<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Search\Index\Updater;

use Mockery\MockInterface;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Domain\Search\Index\Updater\SubjectIndexUpdater;
use Shared\Service\Elastic\ElasticClientInterface;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

class SubjectIndexUpdaterTest extends UnitTestCase
{
    private SubjectIndexUpdater $indexUpdater;
    private ElasticClientInterface&MockInterface $elasticClient;

    protected function setUp(): void
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
