<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Subject;

use Mockery;
use Mockery\MockInterface;
use PublicationApi\Api\Subject\SubjectCreateDto;
use PublicationApi\Api\Subject\SubjectDetailResponseDto;
use PublicationApi\Api\Subject\SubjectLandingPageInputDto;
use PublicationApi\Api\Subject\SubjectMapper;
use PublicationApi\Api\Subject\SubjectResponseDto;
use PublicationApi\Api\Subject\SubjectUpdateDto;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Subject\LandingPageSlug;
use Shared\Domain\Publication\Subject\LandingPageTitle;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Domain\Publication\Subject\SubjectContentNode;
use Shared\Domain\Publication\Subject\SubjectContentTree;
use Shared\Domain\Publication\Subject\SubjectContentTreeStatus;
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

    public function testFromEntityMapsIdAndName(): void
    {
        $result = SubjectMapper::fromEntity($this->subject);

        self::assertInstanceOf(SubjectResponseDto::class, $result);
        self::assertSame($this->subject->getId(), $result->id);
        self::assertSame($this->subject->getName(), $result->name);
    }

    public function testFromEntityWithDetailReturnsNullLandingPageForLegacySubject(): void
    {
        $this->subject->expects('hasLandingPageContent')->andReturnFalse();

        $result = SubjectMapper::fromEntityWithDetail($this->subject);

        self::assertInstanceOf(SubjectDetailResponseDto::class, $result);
        self::assertNull($result->landingPage);
    }

    public function testFromEntityWithDetailMapsConceptLandingPageWithPreviewUrl(): void
    {
        $previewToken = Uuid::v4();

        $this->subject->expects('hasLandingPageContent')->andReturnTrue();
        $this->subject->expects('getLandingPageStatus')->twice()->andReturn(SubjectLandingPageStatus::CONCEPT);
        $this->subject->expects('getLandingPageSlug')->andReturn(LandingPageSlug::create('my-landing-page'));
        $this->subject->expects('getLandingPageTitle')->andReturn(LandingPageTitle::create('My Title'));
        $this->subject->expects('getLandingPageDescription')->andReturn('My description');
        $this->subject->expects('getLandingPageContentTree')->andReturn(null);
        $this->subject->expects('getLandingPageContentTreeStatus')->andReturn(SubjectContentTreeStatus::CONCEPT);
        $this->subject->expects('getLandingPagePreviewToken')->andReturn($previewToken);

        $generator = new SubjectPreviewUrlGenerator('https://example.com');

        $result = SubjectMapper::fromEntityWithDetail($this->subject, $generator);

        self::assertNotNull($result->landingPage);
        self::assertSame(SubjectLandingPageStatus::CONCEPT, $result->landingPage->status);
        self::assertSame('my-landing-page', $result->landingPage->slug);
        self::assertSame('My Title', $result->landingPage->title);
        self::assertSame('My description', $result->landingPage->description);
        self::assertSame(SubjectContentTreeStatus::CONCEPT, $result->landingPage->contentTreeStatus);
        self::assertEquals(new SubjectContentTree(title: '', intro: '', children: [], outro: ''), $result->landingPage->contentTree);
        self::assertSame(
            sprintf(
                'https://example.com/onderwerp/%s/preview/%s',
                $this->subject->getId(),
                $previewToken,
            ),
            $result->landingPage->previewUrl,
        );
    }

    public function testFromEntityWithDetailMapsPublishedLandingPageWithNullPreviewUrl(): void
    {
        $this->subject->expects('hasLandingPageContent')->andReturnTrue();
        $this->subject->expects('getLandingPageStatus')->twice()->andReturn(SubjectLandingPageStatus::PUBLISHED);
        $this->subject->expects('getLandingPageSlug')->andReturn(LandingPageSlug::create('published-landing-page'));
        $this->subject->expects('getLandingPageTitle')->andReturn(LandingPageTitle::create('Published Title'));
        $this->subject->expects('getLandingPageDescription')->andReturn('Published description');
        $this->subject->expects('getLandingPageContentTree')->andReturn(null);
        $this->subject->expects('getLandingPageContentTreeStatus')->andReturn(SubjectContentTreeStatus::CONCEPT);

        $generator = new SubjectPreviewUrlGenerator('https://example.com');

        $result = SubjectMapper::fromEntityWithDetail($this->subject, $generator);

        self::assertNotNull($result->landingPage);
        self::assertSame(SubjectLandingPageStatus::PUBLISHED, $result->landingPage->status);
        self::assertSame('published-landing-page', $result->landingPage->slug);
        self::assertNull($result->landingPage->previewUrl);
    }

    public function testFromEntityWithDetailMapsNormalizedNestedContentTree(): void
    {
        $contentTree = new SubjectContentTree(
            children: [
                new SubjectContentNode('Parent', 'Parent body', [
                    new SubjectContentNode('Child', 'Child body'),
                ]),
            ],
            title: 'Tree title',
            intro: 'Tree intro',
            outro: 'Tree outro',
        );

        $this->subject->expects('hasLandingPageContent')->andReturnTrue();
        $this->subject->expects('getLandingPageStatus')->twice()->andReturn(SubjectLandingPageStatus::PUBLISHED);
        $this->subject->expects('getLandingPageSlug')->andReturn(LandingPageSlug::create('nested-tree'));
        $this->subject->expects('getLandingPageTitle')->andReturn(LandingPageTitle::create('Title'));
        $this->subject->expects('getLandingPageDescription')->andReturn('Description');
        $this->subject->expects('getLandingPageContentTree')->andReturn($contentTree);
        $this->subject->expects('getLandingPageContentTreeStatus')->andReturn(SubjectContentTreeStatus::PUBLISHED);

        $generator = new SubjectPreviewUrlGenerator('https://example.com');

        $result = SubjectMapper::fromEntityWithDetail($this->subject, $generator);

        self::assertNotNull($result->landingPage);
        self::assertSame(SubjectContentTreeStatus::PUBLISHED, $result->landingPage->contentTreeStatus);
        self::assertSame($contentTree, $result->landingPage->contentTree);
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
            new SubjectContentTree(children: $nodes, title: 'Tree title', intro: 'Tree intro', outro: 'Tree outro'),
            SubjectContentTreeStatus::PUBLISHED,
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
        self::assertSame(SubjectContentTreeStatus::PUBLISHED, $subject->getLandingPageContentTreeStatus());
        self::assertSame($landingPage->contentTree, $subject->getLandingPageContentTree());
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
        $contentTree = new SubjectContentTree(children: $nodes, title: 'Tree title', intro: 'Tree intro', outro: 'Tree outro');
        $landingPage = new SubjectLandingPageInputDto(
            $slug,
            $title,
            'Description',
            SubjectLandingPageStatus::CONCEPT,
            $contentTree,
            SubjectContentTreeStatus::PUBLISHED,
        );

        $this->subject->expects('setName')->with('New Name')->andReturnSelf();
        $this->subject->expects('setLandingPage')
            ->withArgs(static fn (
                LandingPageSlug $slug,
                LandingPageTitle $title,
                string $description,
                SubjectLandingPageStatus $status,
                SubjectContentTree $actualContentTree,
                SubjectContentTreeStatus $contentTreeStatus,
            ): bool => $slug === $landingPage->slug
                && $title === $landingPage->title
                && $description === 'Description'
                && $status === SubjectLandingPageStatus::CONCEPT
                && $actualContentTree === $contentTree
                && $contentTreeStatus === SubjectContentTreeStatus::PUBLISHED)
            ->andReturnSelf();

        $dto = new SubjectUpdateDto('New Name');
        $dto->landingPage = $landingPage;

        SubjectMapper::fromUpdateDto($this->subject, $dto);
    }
}
