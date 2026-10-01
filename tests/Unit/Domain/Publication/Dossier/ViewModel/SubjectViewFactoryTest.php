<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\ViewModel;

use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\ViewModel\SubjectViewFactory;
use Shared\Domain\Publication\PublicUrlGenerator;
use Shared\Domain\Publication\Subject\LandingPageSlug;
use Shared\Domain\Publication\Subject\LandingPageTitle;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Domain\Publication\Subject\SubjectContentNode;
use Shared\Domain\Publication\Subject\SubjectContentTree;
use Shared\Domain\Publication\Subject\SubjectPreviewUrlGenerator;
use Shared\Domain\Search\Query\Facet\Definition\SubjectFacet;
use Shared\Domain\Search\Query\Facet\FacetDefinitions;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\Url;
use Symfony\Component\Uid\Uuid;

final class SubjectViewFactoryTest extends UnitTestCase
{
    private PublicUrlGenerator&MockInterface $publicUrlGenerator;
    private SubjectPreviewUrlGenerator&MockInterface $subjectPreviewUrlGenerator;
    private SubjectViewFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->publicUrlGenerator = Mockery::mock(PublicUrlGenerator::class);
        $this->subjectPreviewUrlGenerator = Mockery::mock(SubjectPreviewUrlGenerator::class);

        $this->factory = new SubjectViewFactory(
            $this->publicUrlGenerator,
            new FacetDefinitions([new SubjectFacet()]),
            $this->subjectPreviewUrlGenerator,
        );
    }

    public function testMake(): void
    {
        $subject = Mockery::mock(Subject::class);
        $subject->expects('getId')->andReturn(Uuid::v6());
        $subject->expects('getName')->twice()->andReturn($expectedSubject = 'Foo');
        $subject->expects('hasPublishedLandingPage')->twice()->andReturnFalse();
        $subject->expects('getLandingPageTitle')->andReturnNull();
        $subject->expects('getLandingPageDescription')->andReturnNull();
        $subject->expects('getLandingPageContentTree')->andReturnNull();
        $subject->expects('hasPublishedContentTree')->andReturnFalse();
        $subject->expects('hasLandingPageContent')->andReturnFalse();

        $this->publicUrlGenerator
            ->expects('buildUrlFromRoute')
            ->with('app_search', ['subject' => ['Foo']])
            ->andReturn(Url::create($expectedSearchUrl = 'https://example.com/foo/bar'));

        $this->subjectPreviewUrlGenerator
            ->expects('generatePreviewUrl')
            ->with($subject)
            ->andReturnNull();

        $view = $this->factory->make($subject);

        self::assertEquals($expectedSubject, $view->name);
        self::assertEquals($expectedSearchUrl, $view->searchUrl);
        self::assertNull($view->landingPageUrl);
        self::assertNull($view->landingPagePreviewUrl);
        self::assertFalse($view->hasLandingPageContent);
        self::assertEquals($expectedSearchUrl, $view->landingPageUrlOrSearchUrl);
    }

    public function testMakeWithPublishedLandingPage(): void
    {
        $subject = Mockery::mock(Subject::class);
        $subject->expects('getId')->andReturn(Uuid::v6());
        $subject->expects('getName')->twice()->andReturn('Foo');
        $subject->expects('hasPublishedLandingPage')->twice()->andReturnTrue();
        $subject->expects('getLandingPageSlug')->twice()->andReturn(LandingPageSlug::create('foo'));
        $subject->expects('getLandingPageTitle')->andReturn(LandingPageTitle::create('Foo titel'));
        $subject->expects('getLandingPageDescription')->andReturn('Foo omschrijving');
        $subject->expects('getLandingPageContentTree')->andReturn($contentTree = new SubjectContentTree(
            children: [new SubjectContentNode('Titel', 'Body')],
            title: 'Titel',
            intro: 'Intro',
            outro: 'Outro',
        ));
        $subject->expects('hasPublishedContentTree')->andReturnTrue();
        $subject->expects('hasLandingPageContent')->andReturnTrue();

        $this->publicUrlGenerator
            ->expects('buildUrlFromRoute')
            ->with('app_search', ['subject' => ['Foo']])
            ->andReturn(Url::create('https://example.com/foo/bar'));

        $this->publicUrlGenerator
            ->expects('buildUrlFromRoute')
            ->with('app_subject_landing_page', ['slug' => 'foo'])
            ->andReturn(Url::create($expectedLandingPageUrl = 'https://example.com/onderwerp/foo'));

        $this->subjectPreviewUrlGenerator
            ->expects('generatePreviewUrl')
            ->with($subject)
            ->andReturnNull();

        $view = $this->factory->make($subject);

        self::assertEquals($expectedLandingPageUrl, $view->landingPageUrl);
        self::assertEquals($expectedLandingPageUrl, $view->landingPageUrlOrSearchUrl);
        self::assertNull($view->landingPagePreviewUrl);
        self::assertEquals('Foo titel', $view->landingPageTitle);
        self::assertEquals('Foo omschrijving', $view->landingPageDescription);
        self::assertEquals($contentTree, $view->landingPageContentTree);
        self::assertTrue($view->hasPublishedContentTree);
        self::assertTrue($view->hasLandingPageContent);
    }

    public function testMakeWithConceptLandingPagePreviewUrl(): void
    {
        $subject = Mockery::mock(Subject::class);
        $subject->expects('getId')->andReturn(Uuid::v6());
        $subject->expects('getName')->twice()->andReturn($expectedSubject = 'Foo');
        $subject->expects('hasPublishedLandingPage')->twice()->andReturnFalse();
        $subject->expects('getLandingPageTitle')->andReturnNull();
        $subject->expects('getLandingPageDescription')->andReturnNull();
        $subject->expects('getLandingPageContentTree')->andReturnNull();
        $subject->expects('hasPublishedContentTree')->andReturnFalse();
        $subject->expects('hasLandingPageContent')->andReturnFalse();

        $this->publicUrlGenerator
            ->expects('buildUrlFromRoute')
            ->with('app_search', ['subject' => ['Foo']])
            ->andReturn(Url::create($expectedSearchUrl = 'https://example.com/foo/bar'));

        $this->subjectPreviewUrlGenerator
            ->expects('generatePreviewUrl')
            ->with($subject)
            ->andReturn($expectedPreviewUrl = 'https://example.com/onderwerp/123/preview/abc');

        $view = $this->factory->make($subject);

        self::assertEquals($expectedSubject, $view->name);
        self::assertEquals($expectedSearchUrl, $view->searchUrl);
        self::assertEquals($expectedPreviewUrl, $view->landingPagePreviewUrl);
    }

    #[DataProvider('hasLandingPageContentProvider')]
    public function testMakeHasLandingPageContent(
        ?LandingPageSlug $slug,
        ?LandingPageTitle $title,
        ?string $description,
        bool $hasPublishedContentTree,
        ?SubjectContentTree $contentTree,
        bool $expected,
    ): void {
        $subject = Mockery::mock(Subject::class);
        $subject->expects('getId')->andReturn(Uuid::v6());
        $subject->expects('getName')->twice()->andReturn('Foo');
        $subject->expects('hasPublishedLandingPage')->twice()->andReturnFalse();
        $subject->expects('getLandingPageTitle')->atLeast()->once()->andReturn($title);
        $subject->expects('getLandingPageDescription')->atLeast()->once()->andReturn($description);
        $subject->expects('getLandingPageContentTree')->atLeast()->once()->andReturn($contentTree);
        $subject
            ->expects('hasPublishedContentTree')
            ->atLeast()
            ->once()
            ->andReturn($hasPublishedContentTree);
        $subject->expects('hasLandingPageContent')->andReturn($expected);

        $this->publicUrlGenerator
            ->expects('buildUrlFromRoute')
            ->with('app_search', ['subject' => ['Foo']])
            ->andReturn(Url::create('https://example.com/foo/bar'));

        $this->subjectPreviewUrlGenerator
            ->expects('generatePreviewUrl')
            ->with($subject)
            ->andReturnNull();

        self::assertSame($expected, $this->factory->make($subject)->hasLandingPageContent);
    }

    /**
     * @return array<string,array{0:?LandingPageSlug,1:?LandingPageTitle,2:?string,3:bool,4:?SubjectContentTree,5:bool}>
     */
    public static function hasLandingPageContentProvider(): array
    {
        $contentTree = new SubjectContentTree(
            children: [new SubjectContentNode('Titel', 'Body')],
            title: 'Titel',
            intro: 'Intro',
            outro: 'Outro',
        );

        return [
            'nothing set' => [null, null, null, false, null, false],
            'slug only' => [LandingPageSlug::create('foo'), null, null, false, null, true],
            'title only' => [null, LandingPageTitle::create('Foo titel'), null, false, null, true],
            'description only' => [null, null, 'Foo omschrijving', false, null, true],
            'visible content tree only' => [null, null, null, true, null, true],
            'content tree only' => [null, null, null, false, $contentTree, true],
        ];
    }

    public function testGetSubjectForDossier(): void
    {
        $subject = Mockery::mock(Subject::class);
        $subject->expects('getId')->andReturn(Uuid::v6());
        $subject->expects('getName')->twice()->andReturn($expectedSubject = 'Foo');
        $subject->expects('hasPublishedLandingPage')->twice()->andReturnFalse();
        $subject->expects('getLandingPageTitle')->andReturnNull();
        $subject->expects('getLandingPageDescription')->andReturnNull();
        $subject->expects('getLandingPageContentTree')->andReturnNull();
        $subject->expects('hasPublishedContentTree')->andReturnFalse();
        $subject->expects('hasLandingPageContent')->andReturnFalse();

        $dossier = Mockery::mock(WooDecision::class);
        $dossier->expects('getSubject')->times(2)->andReturn($subject);

        $this->publicUrlGenerator
            ->expects('buildUrlFromRoute')
            ->with('app_search', ['subject' => ['Foo']])
            ->andReturn(Url::create($expectedSearchUrl = 'https://example.com/foo/bar'));

        $this->subjectPreviewUrlGenerator
            ->expects('generatePreviewUrl')
            ->with($subject)
            ->andReturnNull();

        $view = $this->factory->getSubjectForDossier($dossier);

        self::assertNotNull($view);
        self::assertEquals($expectedSubject, $view->name);
        self::assertEquals($expectedSearchUrl, $view->searchUrl);
    }
}
