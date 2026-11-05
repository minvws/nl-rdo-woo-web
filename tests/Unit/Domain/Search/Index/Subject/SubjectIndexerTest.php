<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Search\Index\Subject;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Domain\Search\Index\ElasticDocument;
use Shared\Domain\Search\Index\ElasticDocumentType;
use Shared\Domain\Search\Index\Subject\SubjectIndexer;
use Shared\Domain\Search\Index\Subject\SubjectMapper;
use Shared\Service\Elastic\ElasticService;
use Shared\Tests\Unit\UnitTestCase;

class SubjectIndexerTest extends UnitTestCase
{
    private ElasticService&MockInterface $elasticService;
    private SubjectMapper&MockInterface $subjectMapper;
    private SubjectIndexer $subjectIndexer;

    protected function setUp(): void
    {
        $this->elasticService = Mockery::mock(ElasticService::class);
        $this->subjectMapper = Mockery::mock(SubjectMapper::class);
        $this->subjectIndexer = new SubjectIndexer($this->elasticService, $this->subjectMapper);

        parent::setUp();
    }

    public function testIndexMapsAndUpsertsSubjectDocument(): void
    {
        $subject = Mockery::mock(Subject::class);
        $document = new ElasticDocument('subject-id', ElasticDocumentType::SUBJECT, null, []);

        $this->subjectMapper->expects('map')->with($subject)->andReturn($document);
        $this->elasticService->expects('updateDocument')->with($document);

        $this->subjectIndexer->index($subject);
    }
}
