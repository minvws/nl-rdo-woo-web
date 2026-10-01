<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Search\Index\Updater;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Domain\Search\Index\Updater\SubjectIndexUpdater;
use Shared\Service\Elastic\ElasticClientInterface;
use Shared\Tests\ElasticConfigFactory;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

class SubjectIndexUpdaterTest extends UnitTestCase
{
    private SubjectIndexUpdater $indexUpdater;
    private ElasticClientInterface&MockInterface $elasticClient;

    protected function setUp(): void
    {
        $this->elasticClient = Mockery::mock(ElasticClientInterface::class);

        $this->indexUpdater = new SubjectIndexUpdater(
            $this->elasticClient,
            ElasticConfigFactory::default(),
        );

        parent::setUp();
    }

    public function testUpdateDepartment(): void
    {
        $subject = Mockery::mock(Subject::class);
        $subject->expects('getId')->times(3)->andReturn($subjectId = Uuid::v6());
        $subject->expects('getName')->andReturn('Foo Bar');

        $this->elasticClient->expects('updateByQuery')->with(Mockery::capture($input));

        $this->indexUpdater->update($subject);

        Assert::isArray($input);
        $body = $input['body'];
        Assert::isArray($body);
        $query = $body['query'];
        Assert::isArray($query);
        $script = $body['script'];
        Assert::isArray($script);

        self::assertEquals([
            'should' => [
                ['match' => ['subject.id' => $subjectId]],
                ['nested' => [
                    'path' => 'dossiers',
                    'query' => ['term' => ['dossiers.subject.id' => $subjectId]],
                ]],
            ],
            'minimum_should_match' => 1,
        ], $query['bool']);

        self::assertEquals(
            ['subject' => ['name' => 'Foo Bar', 'id' => $subjectId]],
            $script['params'],
        );
    }
}
