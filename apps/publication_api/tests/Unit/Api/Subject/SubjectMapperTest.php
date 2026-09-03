<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Subject;

use Mockery;
use Mockery\MockInterface;
use PublicationApi\Api\Subject\SubjectCreateDto;
use PublicationApi\Api\Subject\SubjectDetailResponse;
use PublicationApi\Api\Subject\SubjectLandingPageInputDto;
use PublicationApi\Api\Subject\SubjectMapper;
use PublicationApi\Api\Subject\SubjectResponse;
use PublicationApi\Api\Subject\SubjectUpdateDto;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Subject\LandingPageSlug;
use Shared\Domain\Publication\Subject\LandingPageTitle;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Domain\Publication\Subject\SubjectContentNode;
use Shared\Domain\Publication\Subject\SubjectLandingPageStatus;
use Shared\Domain\Publication\Subject\SubjectPreviewUrlGenerator;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

use function sprintf;

class SubjectMapperTest extends UnitTestCase
{
    private Subject&MockInterface $subject;
    private Organisation&MockInterface $organisation;

    protected function setUp(): void
    {
        $this->organisation = Mockery::mock(Organisation::class);
        $this->organisation->allows('getId')->andReturn(Uuid::v6());
        $this->organisation->allows('getName')->andReturn('Test Org');

        $this->subject = Mockery::mock(Subject::class);
        $this->subject->allows('getId')->andReturn(Uuid::v6());
        $this->subject->allows('getName')->andReturn('Test Subject');
        $this->subject->allows('getOrganisation')->andReturn($this->organisation);

        parent::setUp();
    }

    public function testFromEntityReturnsNullLandingPageForLegacySubject(): void
    {
        $this->subject->allows('getLandingPageStatus')->andReturn(null);

        $result = SubjectMapper::fromEntity($this->subject);

        self::assertInstanceOf(SubjectResponse::class, $result);
        self::assertNull($result->landingPage);
    }

    public function testFromEntityWithDetailReturnsNullLandingPageForLegacySubject(): void
    {
        $this->subject->allows('getLandingPageStatus')->andReturn(null);

        $result = SubjectMapper::fromEntityWithDetail($this->subject);

        self::assertInstanceOf(SubjectDetailResponse::class, $result);
        self::assertNull($result->landingPage);
    }

    public function testFromEntityMapsConceptLandingPageWithPreviewUrl(): void
    {
        $previewToken = Uuid::v4();

        $this->subject->allows('getLandingPageStatus')->andReturn(SubjectLandingPageStatus::CONCEPT);
        $this->subject->allows('getLandingPageSlug')->andReturn(LandingPageSlug::create('my-landing-page'));
        $this->subject->allows('getLandingPageTitle')->andReturn(LandingPageTitle::create('My Title'));
        $this->subject->allows('getLandingPageDescription')->andReturn('My description');
        $this->subject->allows('getLandingPageContentTree')->andReturn([]);
        $this->subject->allows('getLandingPagePreviewToken')->andReturn($previewToken);

        $generator = new SubjectPreviewUrlGenerator('https://example.com');

        $result = SubjectMapper::fromEntity($this->subject, $generator);

        self::assertNotNull($result->landingPage);
        self::assertSame(SubjectLandingPageStatus::CONCEPT, $result->landingPage->status);
        self::assertSame('my-landing-page', $result->landingPage->slug);
        self::assertSame('My Title', $result->landingPage->title);
        self::assertSame('My description', $result->landingPage->description);
        self::assertSame([], $result->landingPage->contentTree);
        self::assertSame(
            sprintf(
                'https://example.com/onderwerp/%s/preview/%s',
                $this->subject->getId(),
                $previewToken,
            ),
            $result->landingPage->previewUrl,
        );
    }

    public function testFromEntityMapsPublishedLandingPageWithNullPreviewUrl(): void
    {
        $this->subject->allows('getLandingPageStatus')->andReturn(SubjectLandingPageStatus::PUBLISHED);
        $this->subject->allows('getLandingPageSlug')->andReturn(LandingPageSlug::create('published-landing-page'));
        $this->subject->allows('getLandingPageTitle')->andReturn(LandingPageTitle::create('Published Title'));
        $this->subject->allows('getLandingPageDescription')->andReturn('Published description');
        $this->subject->allows('getLandingPageContentTree')->andReturn([]);

        $generator = new SubjectPreviewUrlGenerator('https://example.com');

        $result = SubjectMapper::fromEntity($this->subject, $generator);

        self::assertNotNull($result->landingPage);
        self::assertSame(SubjectLandingPageStatus::PUBLISHED, $result->landingPage->status);
        self::assertSame('published-landing-page', $result->landingPage->slug);
        self::assertNull($result->landingPage->previewUrl);
    }

    public function testFromEntityMapsNormalizedNestedContentTree(): void
    {
        $nestedTree = [
            [
                'title' => 'Parent',
                'body' => 'Parent body',
                'children' => [
                    ['title' => 'Child', 'body' => 'Child body', 'children' => []],
                ],
            ],
        ];

        $this->subject->allows('getLandingPageStatus')->andReturn(SubjectLandingPageStatus::PUBLISHED);
        $this->subject->allows('getLandingPageSlug')->andReturn(LandingPageSlug::create('nested-tree'));
        $this->subject->allows('getLandingPageTitle')->andReturn(LandingPageTitle::create('Title'));
        $this->subject->allows('getLandingPageDescription')->andReturn('Description');
        $this->subject->allows('getLandingPageContentTree')->andReturn($nestedTree);

        $generator = new SubjectPreviewUrlGenerator('https://example.com');

        $result = SubjectMapper::fromEntity($this->subject, $generator);

        self::assertNotNull($result->landingPage);
        self::assertSame($nestedTree, $result->landingPage->contentTree);
    }

    public function testFromCreateDtoWithLandingPageMapsLandingPage(): void
    {
        $nodes = [new SubjectContentNode('Section', 'Section body')];
        $slug = LandingPageSlug::create('landing-page');
        $title = LandingPageTitle::create('T');
        $landingPage = new SubjectLandingPageInputDto(
            $slug,
            $title,
            'Landing page description',
            SubjectLandingPageStatus::CONCEPT,
            $nodes,
        );

        $dto = new SubjectCreateDto('New subject');
        $dto->landingPage = $landingPage;

        $subject = SubjectMapper::fromCreateDto($dto, $this->organisation);

        self::assertSame('New subject', $subject->getName());
        self::assertSame($this->organisation, $subject->getOrganisation());
        self::assertSame(SubjectLandingPageStatus::CONCEPT, $subject->getLandingPageStatus());
        self::assertSame($slug, $subject->getLandingPageSlug());
        self::assertSame($title, $subject->getLandingPageTitle());
        self::assertSame('Landing page description', $subject->getLandingPageDescription());
        self::assertSame([
            ['title' => 'Section', 'body' => 'Section body', 'children' => []],
        ], $subject->getLandingPageContentTree());
    }

    public function testFromUpdateDtoWithNullLandingPageDoesNotCallSetLandingPage(): void
    {
        $this->subject->expects('setName')->with('New Name')->andReturnSelf();
        $this->subject->expects('setLandingPage')->never();

        $dto = new SubjectUpdateDto('New Name');

        $result = SubjectMapper::fromUpdateDto($this->subject, $dto);

        self::assertSame($this->subject, $result);
    }

    public function testFromUpdateDtoWithLandingPageCallsSetLandingPage(): void
    {
        $nodes = [new SubjectContentNode('t', 'b')];
        $slug = LandingPageSlug::create('landing-page');
        $title = LandingPageTitle::create('T');
        $landingPage = new SubjectLandingPageInputDto(
            $slug,
            $title,
            'Description',
            SubjectLandingPageStatus::CONCEPT,
            $nodes,
        );

        $this->subject->expects('setName')->with('New Name')->andReturnSelf();
        $this->subject->expects('setLandingPage')
            ->withArgs(static fn (
                LandingPageSlug $slug,
                LandingPageTitle $title,
                string $description,
                SubjectLandingPageStatus $status,
                array $contentTree,
            ): bool => $slug === $landingPage->slug
                && $title === $landingPage->title
                && $description === 'Description'
                && $status === SubjectLandingPageStatus::CONCEPT
                && $contentTree === $nodes)
            ->andReturnSelf();

        $dto = new SubjectUpdateDto('New Name');
        $dto->landingPage = $landingPage;

        SubjectMapper::fromUpdateDto($this->subject, $dto);
    }
}
