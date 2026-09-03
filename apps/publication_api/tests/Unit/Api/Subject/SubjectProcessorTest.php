<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Subject;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Validator\Exception\ValidationException;
use Mockery;
use Mockery\MockInterface;
use PublicationApi\Api\Organisation\OrganisationResolver;
use PublicationApi\Api\Organisation\OrganisationResolverInterface;
use PublicationApi\Api\Subject\SubjectCreateDto;
use PublicationApi\Api\Subject\SubjectDetailResponse;
use PublicationApi\Api\Subject\SubjectLandingPageInputDto;
use PublicationApi\Api\Subject\SubjectProcessor;
use PublicationApi\Api\Subject\SubjectUpdateDto;
use PublicationApi\Domain\Exception\EntityNotFoundException;
use PublicationApi\Domain\Exception\ResourceInUseException;
use PublicationApi\Domain\Validator\EntityValidator;
use PublicationApi\FeatureFlag\SubjectLandingPageGuard;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Subject\LandingPageSlug;
use Shared\Domain\Publication\Subject\LandingPageTitle;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Domain\Publication\Subject\SubjectContentNode;
use Shared\Domain\Publication\Subject\SubjectLandingPageStatus;
use Shared\Domain\Publication\Subject\SubjectPreviewUrlGenerator;
use Shared\Domain\Publication\Subject\SubjectRepository;
use Shared\Domain\Publication\Subject\SubjectService;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

use function sprintf;

class SubjectProcessorTest extends UnitTestCase
{
    private OrganisationResolverInterface&MockInterface $organisationResolver;
    private SubjectRepository&MockInterface $subjectRepository;
    private SubjectService&MockInterface $subjectService;
    private EntityValidator&MockInterface $validator;
    private SubjectProcessor $processor;

    protected function setUp(): void
    {
        $this->subjectRepository = Mockery::mock(SubjectRepository::class);
        $this->subjectService = Mockery::mock(SubjectService::class);
        $this->validator = Mockery::mock(EntityValidator::class);
        $this->organisationResolver = Mockery::mock(OrganisationResolver::class);

        $this->processor = new SubjectProcessor(
            $this->organisationResolver,
            $this->subjectRepository,
            $this->subjectService,
            $this->validator,
            new SubjectPreviewUrlGenerator('https://example.com'),
            new SubjectLandingPageGuard(true),
        );

        parent::setUp();
    }

    public function testCreateProcessesSuccessfully(): void
    {
        $organisationId = Uuid::v6();
        $uriVariables = ['organisationId' => $organisationId];
        $organisation = Mockery::mock(Organisation::class);
        $organisation->expects('getId')->andReturn($organisationId);
        $organisation->expects('getName')->andReturn('Test organisation');

        $this->organisationResolver->expects('resolve')->with($uriVariables)->andReturn($organisation);

        $dto = new SubjectCreateDto('Test subject');

        $this->validator->expects('throwExceptionIfNotValid')->with(Mockery::type(Subject::class));
        $this->subjectService->expects('saveNew');

        $result = $this->processor->process($dto, new Post(), ['organisationId' => $organisationId]);

        self::assertInstanceOf(SubjectDetailResponse::class, $result);
    }

    public function testCreateProcessesSuccessfullyWhenFeatureIsDisabled(): void
    {
        $organisationId = Uuid::v6();
        $uriVariables = ['organisationId' => $organisationId];
        $organisation = Mockery::mock(Organisation::class);
        $organisation->expects('getId')->andReturn($organisationId);
        $organisation->expects('getName')->andReturn('Test organisation');

        $this->organisationResolver->expects('resolve')->with($uriVariables)->andReturn($organisation);

        $this->validator->expects('throwExceptionIfNotValid')->with(Mockery::type(Subject::class));
        $this->subjectService->expects('saveNew');

        $processor = new SubjectProcessor(
            $this->organisationResolver,
            $this->subjectRepository,
            $this->subjectService,
            $this->validator,
            new SubjectPreviewUrlGenerator('https://example.com'),
            new SubjectLandingPageGuard(false),
        );

        $result = $processor->process(
            new SubjectCreateDto('Test subject'),
            new Post(),
            $uriVariables,
        );

        self::assertInstanceOf(SubjectDetailResponse::class, $result);
    }

    public function testCreateWithLandingPageThrowsWhenFeatureIsDisabled(): void
    {
        $organisationId = Uuid::v6();
        $uriVariables = ['organisationId' => $organisationId];
        $organisation = Mockery::mock(Organisation::class);
        $landingPage = new SubjectLandingPageInputDto(
            LandingPageSlug::create('landing-page'),
            LandingPageTitle::create('Landing page title'),
            'Landing page description',
            SubjectLandingPageStatus::CONCEPT,
            [new SubjectContentNode('Section', 'Section body')],
        );

        $this->organisationResolver->expects('resolve')->with($uriVariables)->andReturn($organisation);

        $processor = new SubjectProcessor(
            $this->organisationResolver,
            $this->subjectRepository,
            $this->subjectService,
            $this->validator,
            new SubjectPreviewUrlGenerator('https://example.com'),
            new SubjectLandingPageGuard(false),
        );

        $this->expectException(ValidationException::class);

        $processor->process(
            new SubjectCreateDto('Test subject', $landingPage),
            new Post(),
            $uriVariables,
        );
    }

    public function testUpdateProcessesSuccessfully(): void
    {
        $organisationId = Uuid::v6();
        $subjectId = Uuid::v6();
        $uriVariables = ['organisationId' => $organisationId, 'subjectId' => (string) $subjectId];
        $organisation = Mockery::mock(Organisation::class);
        $organisation->expects('getId')->andReturn($organisationId);
        $organisation->expects('getName')->andReturn('Test organisation');

        $subject = Mockery::mock(Subject::class);
        $subject->expects('setName')->with('Updated name')->andReturnSelf();
        $subject->expects('getOrganisation')->andReturn($organisation);
        $subject->expects('getId')->andReturn($subjectId);
        $subject->expects('getName')->andReturn('Updated name');
        $subject->expects('getLandingPageStatus')->andReturn(null);

        $this->organisationResolver->expects('resolve')->with($uriVariables)->andReturn($organisation);
        $this->subjectRepository->expects('findByOrganisationAndId')
            ->with($organisation, Mockery::on(static fn (Uuid $actualSubjectId): bool => $actualSubjectId->equals($subjectId)))
            ->andReturn($subject);

        $this->validator->expects('throwExceptionIfNotValid')->with($subject);
        $this->subjectService->expects('save')->with($subject);

        $dto = new SubjectUpdateDto('Updated name');

        $result = $this->processor->process($dto, new Put(), $uriVariables);

        self::assertInstanceOf(SubjectDetailResponse::class, $result);
    }

    public function testProcessThrowsWhenOrganisationIsUnknown(): void
    {
        $organisationId = Uuid::v6();
        $subjectId = Uuid::v6();
        $uriVariables = ['organisationId' => (string) $organisationId, 'subjectId' => (string) $subjectId];

        $this->organisationResolver
            ->expects('resolve')
            ->with($uriVariables)
            ->andThrow(EntityNotFoundException::for('Organisation', $organisationId));

        $this->expectException(EntityNotFoundException::class);

        $this->processor->process(
            new SubjectUpdateDto('irrelevant'),
            new Put(),
            $uriVariables,
        );
    }

    public function testDeleteProcessesSuccessfully(): void
    {
        $organisationId = Uuid::v6();
        $subjectId = Uuid::v6();
        $uriVariables = ['organisationId' => $organisationId, 'subjectId' => (string) $subjectId];

        $organisation = Mockery::mock(Organisation::class);
        $subject = Mockery::mock(Subject::class);

        $this->organisationResolver->expects('resolve')->with($uriVariables)->andReturn($organisation);
        $this->subjectRepository->expects('findByOrganisationAndId')
            ->with($organisation, Mockery::on(static fn (Uuid $actualSubjectId): bool => $actualSubjectId->equals($subjectId)))
            ->andReturn($subject);
        $this->subjectRepository->expects('isInUse')->with($subject)->andReturn(false);
        $this->subjectRepository->expects('remove')->with($subject, true);

        $result = $this->processor
            ->process(
                new SubjectUpdateDto('irrelevant'),
                new Delete(),
                $uriVariables,
            );

        self::assertNull($result);
    }

    public function testUpdateWithLandingPageProcessesSuccessfully(): void
    {
        $organisationId = Uuid::v6();
        $subjectId = Uuid::v6();
        $uriVariables = ['organisationId' => $organisationId, 'subjectId' => (string) $subjectId];
        $organisation = Mockery::mock(Organisation::class);
        $organisation->expects('getId')->andReturn($organisationId);
        $organisation->expects('getName')->andReturn('Test organisation');

        $subject = new Subject();
        $subject->setName('Original name');
        $subject->setOrganisation($organisation);

        $this->organisationResolver->expects('resolve')->with($uriVariables)->andReturn($organisation);
        $this->subjectRepository->expects('findByOrganisationAndId')
            ->with($organisation, Mockery::on(static fn (Uuid $actualSubjectId): bool => $actualSubjectId->equals($subjectId)))
            ->andReturn($subject);
        $this->validator->expects('throwExceptionIfNotValid')->with($subject);
        $this->subjectService->expects('save')->with($subject);

        $landingPage = new SubjectLandingPageInputDto(
            LandingPageSlug::create('landing-page'),
            LandingPageTitle::create('Landing page title'),
            'Landing page description',
            SubjectLandingPageStatus::CONCEPT,
            [new SubjectContentNode('Section', 'Section body')],
        );

        $result = $this->processor->process(
            new SubjectUpdateDto('Updated name', $landingPage),
            new Put(),
            $uriVariables,
        );

        self::assertInstanceOf(SubjectDetailResponse::class, $result);
        self::assertNotNull($result->landingPage);
        self::assertSame(SubjectLandingPageStatus::CONCEPT, $result->landingPage->status);
        self::assertSame('landing-page', $result->landingPage->slug);
        self::assertSame('Landing page title', $result->landingPage->title);
        self::assertSame('Landing page description', $result->landingPage->description);
        self::assertSame([
            ['title' => 'Section', 'body' => 'Section body', 'children' => []],
        ], $result->landingPage->contentTree);
        self::assertSame(
            sprintf('https://example.com/onderwerp/%s/preview/%s', $subject->getId(), $subject->getLandingPagePreviewToken()),
            $result->landingPage->previewUrl,
        );
    }

    public function testUpdateWithLandingPageThrowsWhenFeatureIsDisabled(): void
    {
        $organisationId = Uuid::v6();
        $subjectId = Uuid::v6();
        $uriVariables = ['organisationId' => $organisationId, 'subjectId' => (string) $subjectId];
        $organisation = Mockery::mock(Organisation::class);
        $subject = new Subject();
        $subject->setName('Original name');
        $subject->setOrganisation($organisation);

        $this->organisationResolver->expects('resolve')->with($uriVariables)->andReturn($organisation);
        $this->subjectRepository->expects('findByOrganisationAndId')
            ->with($organisation, Mockery::on(static fn (Uuid $actualSubjectId): bool => $actualSubjectId->equals($subjectId)))
            ->andReturn($subject);

        $landingPage = new SubjectLandingPageInputDto(
            LandingPageSlug::create('landing-page'),
            LandingPageTitle::create('Landing page title'),
            'Landing page description',
            SubjectLandingPageStatus::CONCEPT,
            [new SubjectContentNode('Section', 'Section body')],
        );
        $processor = new SubjectProcessor(
            $this->organisationResolver,
            $this->subjectRepository,
            $this->subjectService,
            $this->validator,
            new SubjectPreviewUrlGenerator('https://example.com'),
            new SubjectLandingPageGuard(false),
        );

        $this->expectException(ValidationException::class);

        $processor->process(
            new SubjectUpdateDto('Updated name', $landingPage),
            new Put(),
            $uriVariables,
        );
    }

    public function testDeleteThrowsWhenSubjectIsInUse(): void
    {
        $organisationId = Uuid::v6();
        $subjectId = Uuid::v6();
        $uriVariables = ['organisationId' => $organisationId, 'subjectId' => (string) $subjectId];

        $organisation = Mockery::mock(Organisation::class);
        $subject = Mockery::mock(Subject::class);

        $this->organisationResolver->expects('resolve')->with($uriVariables)->andReturn($organisation);
        $this->subjectRepository->expects('findByOrganisationAndId')
            ->with($organisation, Mockery::on(static fn (Uuid $actualSubjectId): bool => $actualSubjectId->equals($subjectId)))
            ->andReturn($subject);
        $this->subjectRepository->expects('isInUse')->with($subject)->andReturn(true);
        $this->subjectRepository->expects('remove')->never();

        $this->expectException(ResourceInUseException::class);

        $this->processor->process(
            new SubjectUpdateDto('irrelevant'),
            new Delete(),
            $uriVariables,
        );
    }
}
