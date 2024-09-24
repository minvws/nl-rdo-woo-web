<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Subject;

use App\Domain\Publication\Subject\Event\SubjectUpdatedEvent;
use App\Domain\Publication\Subject\Subject;
use App\Domain\Publication\Subject\SubjectRepository;
use App\Domain\Publication\Subject\SubjectService;
use App\Entity\Organisation;
use App\Service\Security\Authorization\AuthorizationMatrix;
use Doctrine\ORM\Query;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class SubjectServiceTest extends MockeryTestCase
{
    private SubjectRepository&MockInterface $repository;
    private AuthorizationMatrix&MockInterface $authMatrix;
    private MessageBusInterface&MockInterface $messageBus;
    private SubjectService $subjectService;

    public function setUp(): void
    {
        $this->repository = \Mockery::mock(SubjectRepository::class);
        $this->authMatrix = \Mockery::mock(AuthorizationMatrix::class);
        $this->messageBus = \Mockery::mock(MessageBusInterface::class);

        $this->subjectService = new SubjectService(
            $this->repository,
            $this->authMatrix,
            $this->messageBus,
        );

        parent::setUp();
    }

    public function testCreateNew(): void
    {
        $organisation = \Mockery::mock(Organisation::class);

        $this->authMatrix->expects('getActiveOrganisation')->andReturn($organisation);

        $subject = $this->subjectService->createNew();

        self::assertEquals($organisation, $subject->getOrganisation());
    }

    public function testSaveNew(): void
    {
        $subject = \Mockery::mock(Subject::class);

        $this->repository->expects('save')->with($subject, true);

        $this->subjectService->saveNew($subject);
    }

    public function testSave(): void
    {
        $subject = \Mockery::mock(Subject::class);
        $subject->shouldReceive('getId')->andReturn($subjectId = Uuid::v6());

        $this->repository->expects('save')->with($subject, true);

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (SubjectUpdatedEvent $message) use ($subjectId) {
                return $message->getUuid() === $subjectId;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->subjectService->save($subject);
    }

    public function testGetSubjectsQueryForActiveOrganisation(): void
    {
        $organisation = \Mockery::mock(Organisation::class);

        $this->authMatrix->expects('getActiveOrganisation')->andReturn($organisation);

        $query = \Mockery::mock(Query::class);
        $this->repository->expects('getQueryForOrganisation')->with($organisation)->andReturn($query);

        self::assertEquals(
            $this->subjectService->getSubjectsQueryForActiveOrganisation(),
            $query,
        );
    }
}
