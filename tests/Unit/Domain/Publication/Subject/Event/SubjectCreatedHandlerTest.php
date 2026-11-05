<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Subject\Event;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Subject\Event\SubjectCreatedEvent;
use Shared\Domain\Publication\Subject\Event\SubjectCreatedHandler;
use Shared\Domain\Search\SearchDispatcher;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

class SubjectCreatedHandlerTest extends UnitTestCase
{
    private SearchDispatcher&MockInterface $searchDispatcher;

    protected function setUp(): void
    {
        $this->searchDispatcher = Mockery::mock(SearchDispatcher::class);

        parent::setUp();
    }

    public function testDispatchesSubjectIndexCommand(): void
    {
        $uuid = Uuid::v6();
        $this->searchDispatcher->expects('dispatchIndexSubjectCommand')->with($uuid);

        new SubjectCreatedHandler($this->searchDispatcher)->__invoke(new SubjectCreatedEvent($uuid));
    }
}
