<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\ViewModel;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\ViewModel\SubjectViewFactory;
use Shared\Domain\Publication\PublicUrlGenerator;
use Shared\Domain\Publication\Subject\LandingPageSlug;
use Shared\Domain\Publication\Subject\LandingPageTitle;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Domain\Search\Query\Facet\Definition\SubjectFacet;
use Shared\Domain\Search\Query\Facet\FacetDefinitions;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\Url;
use Symfony\Component\Uid\Uuid;

final class SubjectViewFactoryTest extends UnitTestCase
{
    private PublicUrlGenerator&MockInterface $publicUrlGenerator;
    private SubjectViewFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->publicUrlGenerator = Mockery::mock(PublicUrlGenerator::class);

        $this->factory = new SubjectViewFactory(
            $this->publicUrlGenerator,
            new FacetDefinitions([new SubjectFacet()]),
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
        $subject->expects('hasVisibleLandingPageContentTree')->andReturnFalse();

        $this->publicUrlGenerator
            ->expects('buildUrlFromRoute')
            ->with('app_search', ['subject' => ['Foo']])
            ->andReturn(Url::create($expectedSearchUrl = 'https://example.com/foo/bar'));

        $view = $this->factory->make($subject);

        self::assertEquals($expectedSubject, $view->name);
        self::assertEquals($expectedSearchUrl, $view->searchUrl);
        self::assertNull($view->landingPageUrl);
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
        $subject->expects('getLandingPageContentTree')->andReturn($contentTree = [['type' => 'paragraph']]);
        $subject->expects('hasVisibleLandingPageContentTree')->andReturnTrue();

        $this->publicUrlGenerator
            ->expects('buildUrlFromRoute')
            ->with('app_search', ['subject' => ['Foo']])
            ->andReturn(Url::create('https://example.com/foo/bar'));

        $this->publicUrlGenerator
            ->expects('buildUrlFromRoute')
            ->with('app_subject_landing_page', ['slug' => 'foo'])
            ->andReturn(Url::create($expectedLandingPageUrl = 'https://example.com/onderwerp/foo'));

        $view = $this->factory->make($subject);

        self::assertEquals($expectedLandingPageUrl, $view->landingPageUrl);
        self::assertEquals($expectedLandingPageUrl, $view->landingPageUrlOrSearchUrl);
        self::assertEquals('Foo titel', $view->landingPageTitle);
        self::assertEquals('Foo omschrijving', $view->landingPageDescription);
        self::assertEquals($contentTree, $view->landingPageContentTree);
        self::assertTrue($view->hasVisibleLandingPageContentTree);
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
        $subject->expects('hasVisibleLandingPageContentTree')->andReturnFalse();

        $dossier = Mockery::mock(WooDecision::class);
        $dossier->expects('getSubject')->times(2)->andReturn($subject);

        $this->publicUrlGenerator
            ->expects('buildUrlFromRoute')
            ->with('app_search', ['subject' => ['Foo']])
            ->andReturn(Url::create($expectedSearchUrl = 'https://example.com/foo/bar'));

        $view = $this->factory->getSubjectForDossier($dossier);

        self::assertNotNull($view);
        self::assertEquals($expectedSubject, $view->name);
        self::assertEquals($expectedSearchUrl, $view->searchUrl);
    }
}
