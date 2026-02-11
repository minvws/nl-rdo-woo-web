<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Subject\Event;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Subject\Event\SubjectUpdatedEvent;
use Shared\Domain\Publication\Subject\Event\SubjectUpdatedHandler;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Domain\Publication\Subject\SubjectRepository;
use Shared\Domain\Search\Index\Updater\SubjectIndexUpdater;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\InvalidArgumentException;

class SubjectUpdatedHandlerTest extends UnitTestCase
{
    private SubjectRepository&MockInterface $repository;
    private SubjectIndexUpdater&MockInterface $indexUpdater;
    private SubjectUpdatedHandler $handler;

    protected function setUp(): void
    {
        $this->repository = Mockery::mock(SubjectRepository::class);
        $this->indexUpdater = Mockery::mock(SubjectIndexUpdater::class);

        $this->handler = new SubjectUpdatedHandler(
            $this->repository,
            $this->indexUpdater,
        );

        parent::setUp();
    }

    public function testInvokeSuccessfully(): void
    {
        $subject = Mockery::mock(Subject::class);
        $subject->shouldReceive('getId')->andReturn($subjectId = Uuid::v6());

        $this->repository->expects('find')->with($subjectId)->andReturn($subject);
        $this->indexUpdater->expects('update')->with($subject);

        $this->handler->__invoke(
            SubjectUpdatedEvent::forSubject($subject)
        );
    }

    public function testInvokeThrowsExceptionWhenEntityCannotBeLoaded(): void
    {
        $subject = Mockery::mock(Subject::class);
        $subject->shouldReceive('getId')->andReturn($subjectId = Uuid::v6());

        $this->repository->expects('find')->with($subjectId)->andReturnNull();

        $this->expectException(InvalidArgumentException::class);

        $this->handler->__invoke(
            SubjectUpdatedEvent::forSubject($subject)
        );
    }
}
