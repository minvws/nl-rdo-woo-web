<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Subject\Event;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Subject\Event\SubjectDeletedEvent;
use Shared\Domain\Publication\Subject\Event\SubjectDeletedHandler;
use Shared\Domain\Search\SearchDispatcher;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

class SubjectDeletedHandlerTest extends UnitTestCase
{
    private SearchDispatcher&MockInterface $searchDispatcher;

    protected function setUp(): void
    {
        $this->searchDispatcher = Mockery::mock(SearchDispatcher::class);

        parent::setUp();
    }

    public function testDispatchesDocumentDeleteCommand(): void
    {
        $uuid = Uuid::v6();
        $this->searchDispatcher->expects('dispatchDeleteElasticDocumentCommand')->with($uuid->toRfc4122());

        new SubjectDeletedHandler($this->searchDispatcher)->__invoke(new SubjectDeletedEvent($uuid));
    }
}
