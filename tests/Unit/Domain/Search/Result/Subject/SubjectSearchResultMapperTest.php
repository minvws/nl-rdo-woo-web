<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Search\Result\Subject;

use MinVWS\TypeArray\TypeArray;
use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\ViewModel\SubjectViewFactory;
use Shared\Domain\Publication\Subject\LandingPageTitle;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Domain\Publication\Subject\SubjectRepository;
use Shared\Domain\Search\Index\ElasticDocumentType;
use Shared\Domain\Search\Result\Subject\SubjectSearchResultEntry;
use Shared\Domain\Search\Result\Subject\SubjectSearchResultMapper;
use Shared\Tests\Unit\UnitTestCase;

final class SubjectSearchResultMapperTest extends UnitTestCase
{
    private SubjectRepository&MockInterface $subjectRepository;
    private SubjectViewFactory&MockInterface $subjectViewFactory;
    private SubjectSearchResultMapper $mapper;

    protected function setUp(): void
    {
        $this->subjectRepository = Mockery::mock(SubjectRepository::class);
        $this->subjectViewFactory = Mockery::mock(SubjectViewFactory::class);

        $this->mapper = new SubjectSearchResultMapper(
            $this->subjectRepository,
            $this->subjectViewFactory,
        );
    }

    public function testSupportsOnlySubjects(): void
    {
        self::assertTrue($this->mapper->supports(ElasticDocumentType::SUBJECT));
        self::assertFalse($this->mapper->supports(ElasticDocumentType::WOO_DECISION));
    }

    public function testMapReturnsNullWhenIdIsMissing(): void
    {
        $hit = new TypeArray([]);

        self::assertNull($this->mapper->map($hit));
    }

    public function testMapReturnsNullWhenSubjectCannotBeLoaded(): void
    {
        $hit = new TypeArray(['_id' => 'foo']);

        $this->subjectRepository->expects('find')->with('foo')->andReturnNull();

        self::assertNull($this->mapper->map($hit));
    }

    public function testMapReturnsNullWhenSubjectHasNoPublicLandingPage(): void
    {
        $hit = new TypeArray(['_id' => 'foo']);

        $subject = Mockery::mock(Subject::class);
        $this->subjectRepository->expects('find')->with('foo')->andReturn($subject);
        $this->subjectViewFactory->expects('getLandingPageUrl')->with($subject)->andReturnNull();

        self::assertNull($this->mapper->map($hit));
    }

    public function testMapReturnsEntryWithTitleAndSummaryHighlights(): void
    {
        $hit = new TypeArray([
            '_id' => 'foo',
            'highlight' => [
                'title' => ['[[hl_start]]Onderwerp[[hl_end]] X'],
                'summary' => ['Beschrijving van [[hl_start]]Onderwerp[[hl_end]] X'],
                'pages.content' => ['ignored'],
            ],
        ]);

        $subject = Mockery::mock(Subject::class);
        $subject->expects('getLandingPageTitle')->andReturn(LandingPageTitle::create('Landingspagina X'));
        $subject->expects('getLandingPageDescription')->andReturn('Beschrijving van Onderwerp X');
        $subject->expects('hasVisibleLandingPageContentTree')->andReturnFalse();

        $this->subjectRepository->expects('find')->with('foo')->andReturn($subject);
        $this->subjectViewFactory
            ->expects('getLandingPageUrl')
            ->with($subject)
            ->andReturn('https://example.org/onderwerp/onderwerp-x');

        $entry = $this->mapper->map($hit);

        self::assertInstanceOf(SubjectSearchResultEntry::class, $entry);
        self::assertSame(ElasticDocumentType::SUBJECT, $entry->getType());
        self::assertSame('https://example.org/onderwerp/onderwerp-x', $entry->getSubject()->landingPageUrl);
        self::assertSame('Landingspagina X', $entry->getSubject()->title);
        self::assertSame('Beschrijving van Onderwerp X', $entry->getSubject()->description);
        self::assertSame(
            [
                '[[hl_start]]Onderwerp[[hl_end]] X',
                'Beschrijving van [[hl_start]]Onderwerp[[hl_end]] X',
            ],
            $entry->getHighlights(),
        );
    }

    public function testMapFallsBackToSubjectNameWithoutLandingPageTitle(): void
    {
        $hit = new TypeArray(['_id' => 'foo']);

        $subject = Mockery::mock(Subject::class);
        $subject->expects('getLandingPageTitle')->andReturnNull();
        $subject->expects('getName')->andReturn('Onderwerp X');
        $subject->expects('getLandingPageDescription')->andReturnNull();
        $subject->expects('hasVisibleLandingPageContentTree')->andReturnFalse();

        $this->subjectRepository->expects('find')->with('foo')->andReturn($subject);
        $this->subjectViewFactory
            ->expects('getLandingPageUrl')
            ->with($subject)
            ->andReturn('https://example.org/onderwerp/onderwerp-x');

        $entry = $this->mapper->map($hit);

        self::assertInstanceOf(SubjectSearchResultEntry::class, $entry);
        self::assertSame('Onderwerp X', $entry->getSubject()->title);
        self::assertNull($entry->getSubject()->description);
        self::assertSame([], $entry->getHighlights());
    }

    public function testMapWhenVisible(): void
    {
        $id = $this->getFaker()->uuid();
        $titleHighlight = $this->getFaker()->sentence();
        $contentTreeTitleHighlight = $this->getFaker()->sentence();
        $contentTreeBodyHighlight = $this->getFaker()->sentence();
        $hit = new TypeArray([
            '_id' => $id,
            'highlight' => [
                'title' => [$titleHighlight],
                'content_tree.title' => [$contentTreeTitleHighlight],
                'content_tree.body' => [$contentTreeBodyHighlight],
            ],
        ]);

        $subject = Mockery::mock(Subject::class);
        $subject->expects('getLandingPageTitle')->andReturn(LandingPageTitle::create($this->getFaker()->sentence()));
        $subject->expects('getLandingPageDescription')->andReturnNull();
        $subject->expects('hasVisibleLandingPageContentTree')->andReturnTrue();

        $subjectRepository = Mockery::mock(SubjectRepository::class);
        $subjectRepository->expects('find')->with($id)->andReturn($subject);

        $subjectViewFactory = Mockery::mock(SubjectViewFactory::class);
        $subjectViewFactory->expects('getLandingPageUrl')->with($subject)->andReturn($this->getFaker()->url());

        $entry = new SubjectSearchResultMapper($subjectRepository, $subjectViewFactory)->map($hit);

        self::assertInstanceOf(SubjectSearchResultEntry::class, $entry);
        self::assertSame(
            [$titleHighlight, $contentTreeTitleHighlight, $contentTreeBodyHighlight],
            $entry->getHighlights(),
        );
    }

    public function testMapWhenHidden(): void
    {
        $id = $this->getFaker()->uuid();
        $titleHighlight = $this->getFaker()->sentence();
        $hit = new TypeArray([
            '_id' => $id,
            'highlight' => [
                'title' => [$titleHighlight],
                'content_tree.title' => [$this->getFaker()->sentence()],
                'content_tree.body' => [$this->getFaker()->sentence()],
            ],
        ]);

        $subject = Mockery::mock(Subject::class);
        $subject->expects('getLandingPageTitle')->andReturn(LandingPageTitle::create($this->getFaker()->sentence()));
        $subject->expects('getLandingPageDescription')->andReturnNull();
        $subject->expects('hasVisibleLandingPageContentTree')->andReturnFalse();

        $subjectRepository = Mockery::mock(SubjectRepository::class);
        $subjectRepository->expects('find')->with($id)->andReturn($subject);

        $subjectViewFactory = Mockery::mock(SubjectViewFactory::class);
        $subjectViewFactory->expects('getLandingPageUrl')->with($subject)->andReturn($this->getFaker()->url());

        $entry = new SubjectSearchResultMapper($subjectRepository, $subjectViewFactory)->map($hit);

        self::assertInstanceOf(SubjectSearchResultEntry::class, $entry);
        self::assertSame([$titleHighlight], $entry->getHighlights());
    }
}
