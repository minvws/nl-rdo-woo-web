<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Subject\Event;

use App\Domain\Publication\Subject\Event\SubjectUpdatedEvent;
use App\Domain\Publication\Subject\Event\SubjectUpdatedHandler;
use App\Domain\Publication\Subject\Subject;
use App\Domain\Publication\Subject\SubjectRepository;
use App\Domain\Search\Index\Updater\SubjectIndexUpdater;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\InvalidArgumentException;

class SubjectUpdatedHandlerTest extends MockeryTestCase
{
    private SubjectRepository&MockInterface $repository;
    private SubjectIndexUpdater&MockInterface $indexUpdater;
    private SubjectUpdatedHandler $handler;

    public function setUp(): void
    {
        $this->repository = \Mockery::mock(SubjectRepository::class);
        $this->indexUpdater = \Mockery::mock(SubjectIndexUpdater::class);

        $this->handler = new SubjectUpdatedHandler(
            $this->repository,
            $this->indexUpdater,
        );

        parent::setUp();
    }

    public function testInvokeSuccessfully(): void
    {
        $subject = \Mockery::mock(Subject::class);
        $subject->shouldReceive('getId')->andReturn($subjectId = Uuid::v6());

        $this->repository->expects('find')->with($subjectId)->andReturn($subject);
        $this->indexUpdater->expects('update')->with($subject);

        $this->handler->__invoke(
            SubjectUpdatedEvent::forSubject($subject)
        );
    }

    public function testInvokeThrowsExceptionWhenEntityCannotBeLoaded(): void
    {
        $subject = \Mockery::mock(Subject::class);
        $subject->shouldReceive('getId')->andReturn($subjectId = Uuid::v6());

        $this->repository->expects('find')->with($subjectId)->andReturnNull();

        $this->expectException(InvalidArgumentException::class);

        $this->handler->__invoke(
            SubjectUpdatedEvent::forSubject($subject)
        );
    }
}
