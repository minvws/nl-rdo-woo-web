<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Search\Index\Subject;

use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Shared\Domain\Content\Markdown\MarkdownFormatter;
use Shared\Domain\Publication\Subject\LandingPageSlug;
use Shared\Domain\Publication\Subject\LandingPageTitle;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Domain\Publication\Subject\SubjectContentNode;
use Shared\Domain\Publication\Subject\SubjectContentTree;
use Shared\Domain\Publication\Subject\SubjectContentTreeStatus;
use Shared\Domain\Publication\Subject\SubjectLandingPageStatus;
use Shared\Domain\Search\Index\Subject\SubjectMapper;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Subject\SubjectFactory;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

use function array_intersect_key;
use function json_decode;
use function json_encode;
use function sprintf;

use const JSON_THROW_ON_ERROR;

class SubjectMapperTest extends UnitTestCase
{
    #[DataProvider('landingPageVisibilityCases')]
    public function testPublishedLandingPageHasVisibleTreeAndConceptLandingPageDoesNot(
        SubjectLandingPageStatus $pageStatus,
        bool $expectedVisibility,
    ): void {
        $subject = $this->createSubject($pageStatus, 'Landing page title');

        $markdownFormatter = Mockery::mock(MarkdownFormatter::class);
        $markdownFormatter->expects('toPlainText')
            ->with('Landing page summary')
            ->andReturn('Landing page summary');
        $markdownFormatter->expects('toPlainText')
            ->with("\n\n")
            ->andReturn('');

        $document = new SubjectMapper($markdownFormatter)->map($subject);

        self::assertSame($expectedVisibility, $document->getFields()['has_visible_content_tree']);
    }

    /**
     * @return array<string, array{SubjectLandingPageStatus, bool}>
     */
    public static function landingPageVisibilityCases(): array
    {
        return [
            'published page with present published tree' => [SubjectLandingPageStatus::PUBLISHED, true],
            'concept page with present published tree' => [SubjectLandingPageStatus::CONCEPT, false],
        ];
    }

    #[DataProvider('subjectMetadataCases')]
    public function testSubjectMetadataIsMappedWithTitleFallback(?string $title, string $expectedTitle): void
    {
        $subject = $this->createSubject(SubjectLandingPageStatus::PUBLISHED, $title);

        $markdownFormatter = Mockery::mock(MarkdownFormatter::class);
        $markdownFormatter->expects('toPlainText')->with('Landing page summary')->andReturn('Landing page summary');
        $markdownFormatter->expects('toPlainText')->with("\n\n")->andReturn('');

        $document = new SubjectMapper($markdownFormatter)->map($subject);
        $values = json_decode(json_encode($document->getDocumentValues(), JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
        Assert::isArray($values);

        $expected = [
            'title' => $expectedTitle,
            'summary' => 'Landing page summary',
            'status' => 'published',
            'subject' => [
                'id' => '00000000-0000-4000-8000-000000000001',
                'name' => 'Subject name',
            ],
            'organisation_ids' => ['00000000-0000-4000-8000-000000000002'],
            'type' => 'subject',
        ];
        self::assertSame($expected, array_intersect_key($values, $expected));
    }

    /**
     * @return array<string, array{?string, string}>
     */
    public static function subjectMetadataCases(): array
    {
        return [
            'explicit landing page title' => ['Landing page title', 'Landing page title'],
            'null title falls back to subject name' => [null, 'Subject name'],
        ];
    }

    public function testSummaryIsConvertedToPlainText(): void
    {
        $description = $this->getFaker()->sentence();
        $plainDescription = $this->getFaker()->sentence();

        $subject = SubjectFactory::new()
            ->withoutPersisting()
            ->create([
                'organisation' => OrganisationFactory::new(['departments' => []]),
                'landingPageDescription' => $description,
            ]);

        $markdownFormatter = Mockery::mock(MarkdownFormatter::class);
        $markdownFormatter->expects('toPlainText')->with($description)->andReturn($plainDescription);

        $document = new SubjectMapper($markdownFormatter)->map($subject);

        self::assertSame($plainDescription, $document->getFields()['summary']);
    }

    public function testContentTreeIsMapped(): void
    {
        $treeTitle = $this->getFaker()->sentence();
        $intro = $this->getFaker()->sentence();
        $outro = $this->getFaker()->sentence();
        $plainIntroAndOutro = $this->getFaker()->sentence();

        $firstTitle = $this->getFaker()->sentence();
        $firstBody = $this->getFaker()->sentence();
        $plainFirstBody = $this->getFaker()->sentence();
        $childTitle = $this->getFaker()->sentence();
        $childBody = $this->getFaker()->sentence();
        $plainChildBody = $this->getFaker()->sentence();
        $grandchildTitle = $this->getFaker()->sentence();
        $grandchildBody = $this->getFaker()->sentence();
        $plainGrandchildBody = $this->getFaker()->sentence();
        $secondTitle = $this->getFaker()->sentence();
        $secondBody = $this->getFaker()->sentence();
        $plainSecondBody = $this->getFaker()->sentence();

        $children = [
            new SubjectContentNode($firstTitle, $firstBody, [
                new SubjectContentNode($childTitle, $childBody, [
                    new SubjectContentNode($grandchildTitle, $grandchildBody),
                ]),
            ]),
            new SubjectContentNode($secondTitle, $secondBody),
        ];
        $subject = SubjectFactory::new()
            ->withoutPersisting()
            ->create([
                'organisation' => OrganisationFactory::new(['departments' => []]),
                'landingPageContentTree' => new SubjectContentTree($treeTitle, $intro, $children, $outro),
            ]);

        $markdownFormatter = Mockery::mock(MarkdownFormatter::class);
        $markdownFormatter->expects('toPlainText')->with(sprintf("%s\n\n%s", $intro, $outro))->andReturn($plainIntroAndOutro);
        $markdownFormatter->expects('toPlainText')->with($firstBody)->andReturn($plainFirstBody);
        $markdownFormatter->expects('toPlainText')->with($childBody)->andReturn($plainChildBody);
        $markdownFormatter->expects('toPlainText')->with($grandchildBody)->andReturn($plainGrandchildBody);
        $markdownFormatter->expects('toPlainText')->with($secondBody)->andReturn($plainSecondBody);

        $document = new SubjectMapper($markdownFormatter)->map($subject);

        self::assertSame(
            [
                ['title' => $treeTitle, 'body' => $plainIntroAndOutro],
                ['title' => $firstTitle, 'body' => $plainFirstBody],
                ['title' => $childTitle, 'body' => $plainChildBody],
                ['title' => $grandchildTitle, 'body' => $plainGrandchildBody],
                ['title' => $secondTitle, 'body' => $plainSecondBody],
            ],
            $document->getFields()['content_tree'],
        );
    }

    public function testMissingContentTreeMapsWhenEmpty(): void
    {
        $subject = SubjectFactory::new()
            ->withoutPersisting()
            ->create([
                'organisation' => OrganisationFactory::new(['departments' => []]),
            ]);

        $document = new SubjectMapper(Mockery::mock(MarkdownFormatter::class))->map($subject);

        self::assertSame([], $document->getFields()['content_tree']);
        self::assertFalse($document->getFields()['has_visible_content_tree']);
    }

    private function createSubject(SubjectLandingPageStatus $pageStatus, ?string $title): Subject
    {
        $organisation = OrganisationFactory::new()->withoutPersisting()->create([
            'id' => Uuid::fromString('00000000-0000-4000-8000-000000000002'),
            'departments' => [],
        ]);

        $subject = SubjectFactory::new()->withoutPersisting()->create([
            'id' => Uuid::fromString('00000000-0000-4000-8000-000000000001'),
            'name' => 'Subject name',
            'organisation' => $organisation,
            'landingPageSlug' => LandingPageSlug::create('subject-name'),
            'landingPageDescription' => 'Landing page summary',
            'landingPageStatus' => $pageStatus,
            'landingPageContentTree' => new SubjectContentTree('', '', [], ''),
            'landingPageContentTreeStatus' => SubjectContentTreeStatus::PUBLISHED,
        ]);
        if ($title !== null) {
            $subject->setLandingPageTitle(LandingPageTitle::create($title));
        }

        return $subject;
    }
}
