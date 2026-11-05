<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Search\Index\Subject;

use PHPUnit\Framework\Attributes\DataProvider;
use Shared\Domain\Publication\Subject\LandingPageSlug;
use Shared\Domain\Publication\Subject\LandingPageTitle;
use Shared\Domain\Publication\Subject\Subject;
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

use const JSON_THROW_ON_ERROR;

class SubjectMapperTest extends UnitTestCase
{
    #[DataProvider('landingPageVisibilityCases')]
    public function testPublishedLandingPageHasVisibleTreeAndConceptLandingPageDoesNot(
        SubjectLandingPageStatus $pageStatus,
        bool $expectedVisibility,
    ): void {
        $subject = $this->createSubject($pageStatus, 'Landing page title');

        $document = new SubjectMapper()->map($subject);

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

        $document = new SubjectMapper()->map($subject);
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
