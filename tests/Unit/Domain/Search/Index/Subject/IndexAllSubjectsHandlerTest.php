<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Search\Index\Subject;

use Doctrine\ORM\Query;
use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Subject\SubjectRepository;
use Shared\Domain\Search\Index\Subject\IndexAllSubjectsCommand;
use Shared\Domain\Search\Index\Subject\IndexAllSubjectsHandler;
use Shared\Domain\Search\SearchDispatcher;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

class IndexAllSubjectsHandlerTest extends UnitTestCase
{
    private SubjectRepository&MockInterface $subjectRepository;
    private SearchDispatcher&MockInterface $searchDispatcher;
    private IndexAllSubjectsHandler $handler;

    protected function setUp(): void
    {
        $this->subjectRepository = Mockery::mock(SubjectRepository::class);
        $this->searchDispatcher = Mockery::mock(SearchDispatcher::class);
        $this->handler = new IndexAllSubjectsHandler($this->subjectRepository, $this->searchDispatcher);

        parent::setUp();
    }

    public function testDispatchesOneIndexCommandPerSubjectId(): void
    {
        $firstId = Uuid::v6();
        $secondId = Uuid::v6();
        $query = Mockery::mock(Query::class);
        $query->expects('toIterable')->andReturn([
            ['id' => $firstId],
            ['id' => $secondId],
        ]);

        $this->subjectRepository->expects('getAllSubjectIdsWithLandingPageQuery')->andReturn($query);
        $this->searchDispatcher->expects('dispatchIndexSubjectCommand')->with($firstId);
        $this->searchDispatcher->expects('dispatchIndexSubjectCommand')->with($secondId);

        $this->handler->__invoke(new IndexAllSubjectsCommand());
    }
}
