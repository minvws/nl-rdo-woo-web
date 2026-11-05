<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Search\Index\Subject;

use Mockery;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Shared\Domain\Publication\Subject\LandingPageSlug;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Domain\Publication\Subject\SubjectRepository;
use Shared\Domain\Search\Index\Subject\IndexSubjectCommand;
use Shared\Domain\Search\Index\Subject\IndexSubjectHandler;
use Shared\Domain\Search\Index\Subject\SubjectIndexer;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

class IndexSubjectHandlerTest extends UnitTestCase
{
    private SubjectRepository&MockInterface $subjectRepository;
    private SubjectIndexer&MockInterface $subjectIndexer;
    private LoggerInterface&MockInterface $logger;
    private IndexSubjectHandler $handler;

    protected function setUp(): void
    {
        $this->subjectRepository = Mockery::mock(SubjectRepository::class);
        $this->subjectIndexer = Mockery::mock(SubjectIndexer::class);
        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->handler = new IndexSubjectHandler(
            $this->subjectRepository,
            $this->subjectIndexer,
            $this->logger,
        );

        parent::setUp();
    }

    public function testIndexesSubjectWithLandingPage(): void
    {
        $uuid = Uuid::v6();
        $subject = Mockery::mock(Subject::class);
        $subject->expects('getLandingPageSlug')->andReturn(Mockery::mock(LandingPageSlug::class));

        $this->subjectRepository->expects('find')->with($uuid)->andReturn($subject);
        $this->subjectIndexer->expects('index')->with($subject);

        $this->handler->__invoke(new IndexSubjectCommand($uuid));
    }

    public function testWarningIsLoggedWithoutDeletingWhenSubjectHasNoLandingPage(): void
    {
        $uuid = Uuid::v6();
        $subject = Mockery::mock(Subject::class);
        $subject->expects('getLandingPageSlug')->andReturnNull();

        $this->subjectRepository->expects('find')->with($uuid)->andReturn($subject);
        $this->subjectIndexer->expects('index')->never();
        $this->logger->expects('warning')->with('Subject has no landing-page slug for IndexSubjectCommand', [
            'uuid' => $uuid,
        ]);

        $this->handler->__invoke(new IndexSubjectCommand($uuid));
    }

    public function testWarningIsLoggedWithoutDeletingWhenSubjectNoLongerExists(): void
    {
        $uuid = Uuid::v6();
        $this->subjectRepository->expects('find')->with($uuid)->andReturnNull();
        $this->subjectIndexer->expects('index')->never();
        $this->logger->expects('warning')->with('No subject entity found for IndexSubjectCommand', [
            'uuid' => $uuid,
        ]);

        $this->handler->__invoke(new IndexSubjectCommand($uuid));
    }

    public function testErrorIsLoggedWhenSubjectRetrievalThrows(): void
    {
        $uuid = Uuid::v6();
        $this->subjectRepository->expects('find')->with($uuid)->andThrow(new RuntimeException('Retrieval failed'));
        $this->subjectIndexer->expects('index')->never();
        $this->logger->expects('error')->with('Failed to update subject in elasticsearch', [
            'uuid' => $uuid,
            'exception' => 'Retrieval failed',
        ]);

        $this->handler->__invoke(new IndexSubjectCommand($uuid));
    }

    public function testErrorIsLoggedWhenSubjectIndexingThrows(): void
    {
        $uuid = Uuid::v6();
        $subject = Mockery::mock(Subject::class);
        $subject->expects('getLandingPageSlug')->andReturn(Mockery::mock(LandingPageSlug::class));

        $this->subjectRepository->expects('find')->with($uuid)->andReturn($subject);
        $this->subjectIndexer->expects('index')->with($subject)->andThrow(new RuntimeException('Indexing failed'));
        $this->logger->expects('error')->with('Failed to update subject in elasticsearch', [
            'uuid' => $uuid,
            'exception' => 'Indexing failed',
        ]);

        $this->handler->__invoke(new IndexSubjectCommand($uuid));
    }
}
