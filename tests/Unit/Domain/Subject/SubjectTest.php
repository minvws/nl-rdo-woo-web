<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Subject;

use Doctrine\Common\Collections\Collection;
use Mockery;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Subject\LandingPageSlug;
use Shared\Domain\Publication\Subject\LandingPageTitle;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Domain\Publication\Subject\SubjectContentNode;
use Shared\Domain\Publication\Subject\SubjectLandingPageStatus;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

class SubjectTest extends UnitTestCase
{
    public function testGettersAndSetters(): void
    {
        $subject = new Subject();
        self::assertNotEmpty($subject->getId()->toRfc4122());

        $subject->setName($name = 'foo');
        self::assertEquals($name, $subject->getName());

        $subject->setOrganisation($organisation = Mockery::mock(Organisation::class));
        self::assertEquals($organisation, $subject->getOrganisation());

        $subject->setDossiers($dossiers = Mockery::mock(Collection::class));
        self::assertEquals($dossiers, $subject->getDossiers());
    }

    public function testSetAndGetLandingPage(): void
    {
        $subject = new Subject();
        $slug = LandingPageSlug::create('landing-page');
        $title = LandingPageTitle::create('Landing page title');
        $contentTree = [
            new SubjectContentNode(
                'Root title',
                'Root body',
                [
                    new SubjectContentNode('Child title', 'Child body'),
                ],
            ),
        ];

        $result = $subject->setLandingPage(
            $slug,
            $title,
            'Landing page description',
            SubjectLandingPageStatus::PUBLISHED,
            $contentTree,
        );

        self::assertSame($subject, $result);
        self::assertSame($slug, $subject->getLandingPageSlug());
        self::assertSame($title, $subject->getLandingPageTitle());
        self::assertSame('Landing page description', $subject->getLandingPageDescription());
        self::assertSame(SubjectLandingPageStatus::PUBLISHED, $subject->getLandingPageStatus());
        self::assertNull($subject->getLandingPagePreviewToken());
        self::assertSame(
            [
                [
                    'title' => 'Root title',
                    'body' => 'Root body',
                    'children' => [
                        [
                            'title' => 'Child title',
                            'body' => 'Child body',
                            'children' => [],
                        ],
                    ],
                ],
            ],
            $subject->getLandingPageContentTree(),
        );
    }

    public function testSetLandingPageGeneratesAndPreservesConceptPreviewToken(): void
    {
        $subject = new Subject();

        $subject->setLandingPage(
            LandingPageSlug::create('landing-page'),
            LandingPageTitle::create('Landing page title'),
            'Landing page description',
            SubjectLandingPageStatus::CONCEPT,
            [],
        );

        $previewToken = $subject->getLandingPagePreviewToken();
        self::assertInstanceOf(Uuid::class, $previewToken);

        $subject->setLandingPage(
            LandingPageSlug::create('updated-landing-page'),
            LandingPageTitle::create('Updated landing page title'),
            'Updated landing page description',
            SubjectLandingPageStatus::CONCEPT,
            [],
        );

        self::assertSame($previewToken, $subject->getLandingPagePreviewToken());
    }

    public function testSetLandingPageStoresAllFields(): void
    {
        $subject = new Subject();

        $node = new SubjectContentNode('Title', 'Body text');
        $slug = LandingPageSlug::create('page-title');
        $title = LandingPageTitle::create('Page title');
        $subject->setLandingPage($slug, $title, 'Page description', SubjectLandingPageStatus::CONCEPT, [$node]);

        self::assertSame($slug, $subject->getLandingPageSlug());
        self::assertSame($title, $subject->getLandingPageTitle());
        self::assertSame('Page description', $subject->getLandingPageDescription());
        self::assertSame(SubjectLandingPageStatus::CONCEPT, $subject->getLandingPageStatus());
        self::assertNotNull($subject->getLandingPagePreviewToken());
        self::assertSame(
            [['title' => 'Title', 'body' => 'Body text', 'children' => []]],
            $subject->getLandingPageContentTree(),
        );
    }

    public function testNullLandingPageFieldsByDefault(): void
    {
        $subject = new Subject();

        self::assertNull($subject->getLandingPageSlug());
        self::assertNull($subject->getLandingPageTitle());
        self::assertNull($subject->getLandingPageDescription());
        self::assertNull($subject->getLandingPageStatus());
        self::assertNull($subject->getLandingPagePreviewToken());
        self::assertNull($subject->getLandingPageContentTree());
    }

    public function testThreeLevelNodeNormalization(): void
    {
        $child2 = new SubjectContentNode('L3', 'body3');
        $child1 = new SubjectContentNode('L2', 'body2', [$child2]);
        $root = new SubjectContentNode('L1', 'body1', [$child1]);
        $subject = new Subject();
        $subject->setLandingPage(
            LandingPageSlug::create('three-level'),
            LandingPageTitle::create('T'),
            'D',
            SubjectLandingPageStatus::CONCEPT,
            [$root],
        );

        /** @var list<array{title: string, body: string, children: list<array{title: string, body: string, children: list<array{title: string, body: string, children: list<mixed>}>}>}> $tree */
        $tree = $subject->getLandingPageContentTree();
        self::assertIsArray($tree);
        self::assertSame('L1', $tree[0]['title']);
        self::assertSame('L2', $tree[0]['children'][0]['title']);
        self::assertSame('L3', $tree[0]['children'][0]['children'][0]['title']);
    }

    public function testTokenStableAcrossConceptToConceptSave(): void
    {
        $subject = new Subject();
        $subject->setLandingPage(
            LandingPageSlug::create('concept-to-concept'),
            LandingPageTitle::create('T'),
            'D',
            SubjectLandingPageStatus::CONCEPT,
            [],
        );

        $firstToken = $subject->getLandingPagePreviewToken();
        self::assertNotNull($firstToken);

        $subject->setLandingPage(
            LandingPageSlug::create('concept-to-concept-updated'),
            LandingPageTitle::create('T2'),
            'D2',
            SubjectLandingPageStatus::CONCEPT,
            [],
        );

        self::assertSame($firstToken->toRfc4122(), $subject->getLandingPagePreviewToken()?->toRfc4122());
    }

    public function testTokenStableAfterConceptToPublished(): void
    {
        $subject = new Subject();
        $subject->setLandingPage(
            LandingPageSlug::create('concept-to-published'),
            LandingPageTitle::create('T'),
            'D',
            SubjectLandingPageStatus::CONCEPT,
            [],
        );

        $firstToken = $subject->getLandingPagePreviewToken();
        self::assertNotNull($firstToken);

        $subject->setLandingPage(
            LandingPageSlug::create('concept-to-published'),
            LandingPageTitle::create('T'),
            'D',
            SubjectLandingPageStatus::PUBLISHED,
            [],
        );

        self::assertSame($firstToken->toRfc4122(), $subject->getLandingPagePreviewToken()?->toRfc4122());
    }

    public function testPublishedDoesNotCreateToken(): void
    {
        $subject = new Subject();
        $subject->setLandingPage(
            LandingPageSlug::create('published'),
            LandingPageTitle::create('T'),
            'D',
            SubjectLandingPageStatus::PUBLISHED,
            [],
        );

        self::assertNull($subject->getLandingPagePreviewToken());
    }
}
