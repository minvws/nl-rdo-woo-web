<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Subject;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Mockery;
use Mockery\MockInterface;
use PublicationApi\Api\Subject\SubjectCreateDto;
use PublicationApi\Api\Subject\SubjectDetailResponse;
use PublicationApi\Api\Subject\SubjectProcessor;
use PublicationApi\Api\Subject\SubjectUpdateDto;
use PublicationApi\Domain\Exception\ResourceInUseException;
use PublicationApi\Domain\Validator\EntityValidator;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Organisation\OrganisationRepository;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Domain\Publication\Subject\SubjectRepository;
use Shared\Domain\Publication\Subject\SubjectService;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

class SubjectProcessorTest extends UnitTestCase
{
    private OrganisationRepository&MockInterface $organisationRepository;
    private SubjectRepository&MockInterface $subjectRepository;
    private SubjectService&MockInterface $subjectService;
    private EntityValidator&MockInterface $validator;
    private SubjectProcessor $processor;

    protected function setUp(): void
    {
        $this->organisationRepository = Mockery::mock(OrganisationRepository::class);
        $this->subjectRepository = Mockery::mock(SubjectRepository::class);
        $this->subjectService = Mockery::mock(SubjectService::class);
        $this->validator = Mockery::mock(EntityValidator::class);

        $this->processor = new SubjectProcessor(
            $this->organisationRepository,
            $this->subjectRepository,
            $this->subjectService,
            $this->validator,
        );

        parent::setUp();
    }

    public function testCreateProcessesSuccessfully(): void
    {
        $organisationId = Uuid::v6();
        $organisation = Mockery::mock(Organisation::class);
        $organisation->expects('getId')->andReturn($organisationId);
        $organisation->expects('getName')->andReturn('Test organisation');

        $this->organisationRepository->expects('find')->with($organisationId)->andReturn($organisation);

        $dto = new SubjectCreateDto('Test subject');

        $this->validator->expects('throwExceptionIfNotValid')->with(Mockery::type(Subject::class));
        $this->subjectService->expects('saveNew');

        $result = $this->processor->process($dto, new Post(), ['organisationId' => $organisationId]);

        self::assertInstanceOf(SubjectDetailResponse::class, $result);
    }

    public function testUpdateProcessesSuccessfully(): void
    {
        $organisationId = Uuid::v6();
        $organisation = Mockery::mock(Organisation::class);
        $organisation->expects('getId')->andReturn($organisationId);
        $organisation->expects('getName')->andReturn('Test organisation');
        $subjectId = Uuid::v6();

        $subject = Mockery::mock(Subject::class);
        $subject->expects('setName')->with('Updated name')->andReturnSelf();
        $subject->expects('getOrganisation')->andReturn($organisation);
        $subject->expects('getId')->andReturn($subjectId);
        $subject->expects('getName')->andReturn('Updated name');

        $this->organisationRepository->expects('find')->with($organisationId)->andReturn($organisation);
        $this->subjectRepository->expects('find')->with($subjectId)->andReturn($subject);

        $this->validator->expects('throwExceptionIfNotValid')->with($subject);
        $this->subjectService->expects('save')->with($subject);

        $dto = new SubjectUpdateDto('Updated name');

        $result = $this->processor->process($dto, new Put(), ['organisationId' => $organisationId, 'subjectId' => $subjectId]);

        self::assertInstanceOf(SubjectDetailResponse::class, $result);
    }

    public function testDeleteProcessesSuccessfully(): void
    {
        $organisationId = Uuid::v6();
        $subjectId = Uuid::v6();

        $subject = Mockery::mock(Subject::class);

        $this->organisationRepository->expects('find')->with($organisationId)->andReturn(Mockery::mock(Organisation::class));
        $this->subjectRepository->expects('find')->with($subjectId)->andReturn($subject);
        $this->subjectRepository->expects('isInUse')->with($subject)->andReturn(false);
        $this->subjectRepository->expects('remove')->with($subject, true);

        $result = $this->processor
            ->process(
                new SubjectUpdateDto('irrelevant'),
                new Delete(),
                ['organisationId' => $organisationId, 'subjectId' => $subjectId],
            );

        self::assertNull($result);
    }

    public function testDeleteThrowsWhenSubjectIsInUse(): void
    {
        $organisationId = Uuid::v6();
        $subjectId = Uuid::v6();

        $subject = Mockery::mock(Subject::class);

        $this->organisationRepository->expects('find')->with($organisationId)->andReturn(Mockery::mock(Organisation::class));
        $this->subjectRepository->expects('find')->with($subjectId)->andReturn($subject);
        $this->subjectRepository->expects('isInUse')->with($subject)->andReturn(true);
        $this->subjectRepository->expects('remove')->never();

        $this->expectException(ResourceInUseException::class);

        $this->processor->process(
            new SubjectUpdateDto('irrelevant'),
            new Delete(),
            ['organisationId' => $organisationId, 'subjectId' => $subjectId],
        );
    }
}
