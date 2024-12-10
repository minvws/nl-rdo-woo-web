<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\ViewModel;

use App\Domain\Publication\Dossier\Type\ViewModel\SubjectViewFactory;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\WooDecision;
use App\Domain\Publication\Subject\Subject;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Symfony\Component\Routing\Generator\UrlGenerator;

final class SubjectViewFactoryTest extends UnitTestCase
{
    private UrlGenerator&MockInterface $urlGenerator;
    private SubjectViewFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->urlGenerator = \Mockery::mock(UrlGenerator::class);

        $this->factory = new SubjectViewFactory(
            $this->urlGenerator,
        );
    }

    public function testMake(): void
    {
        /** @var Subject&MockInterface $subject */
        $subject = \Mockery::mock(Subject::class);
        $subject->shouldReceive('getName')->andReturn($expectedSubject = 'Foo');

        $this->urlGenerator
            ->expects('generate')
            ->with('app_search', ['subject' => ['Foo']])
            ->andReturn($expectedSearchUrl = '/foo/bar');

        $view = $this->factory->make($subject);

        self::assertEquals($expectedSubject, $view->name);
        self::assertEquals($expectedSearchUrl, $view->searchUrl);
    }

    public function testGetSubjectForDossier(): void
    {
        /** @var Subject&MockInterface $subject */
        $subject = \Mockery::mock(Subject::class);
        $subject->shouldReceive('getName')->andReturn($expectedSubject = 'Foo');

        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('getSubject')->andReturn($subject);

        $this->urlGenerator
            ->expects('generate')
            ->with('app_search', ['subject' => ['Foo']])
            ->andReturn($expectedSearchUrl = '/foo/bar');

        $view = $this->factory->getSubjectForDossier($dossier);

        self::assertNotNull($view);
        self::assertEquals($expectedSubject, $view->name);
        self::assertEquals($expectedSearchUrl, $view->searchUrl);
    }
}
