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
use Shared\Domain\Publication\Subject\SubjectContentTree;
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
        $contentTree = new SubjectContentTree(
            children: [
                new SubjectContentNode(
                    'Root title',
                    'Root body',
                    [
                        new SubjectContentNode('Child title', 'Child body'),
                    ],
                ),
            ],
            title: 'Content tree title',
            intro: 'Content tree intro',
            outro: 'Content tree outro',
        );

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
        self::assertSame($contentTree, $subject->getLandingPageContentTree());
    }

    public function testSetLandingPageGeneratesAndPreservesConceptPreviewToken(): void
    {
        $subject = new Subject();

        $subject->setLandingPage(
            LandingPageSlug::create('landing-page'),
            LandingPageTitle::create('Landing page title'),
            'Landing page description',
            SubjectLandingPageStatus::CONCEPT,
            new SubjectContentTree(title: '', intro: '', children: [], outro: ''),
        );

        $previewToken = $subject->getLandingPagePreviewToken();
        self::assertInstanceOf(Uuid::class, $previewToken);

        $subject->setLandingPage(
            LandingPageSlug::create('updated-landing-page'),
            LandingPageTitle::create('Updated landing page title'),
            'Updated landing page description',
            SubjectLandingPageStatus::CONCEPT,
            new SubjectContentTree(title: '', intro: '', children: [], outro: ''),
        );

        self::assertSame($previewToken, $subject->getLandingPagePreviewToken());
    }

    public function testSetLandingPageStoresAllFields(): void
    {
        $subject = new Subject();

        $node = new SubjectContentNode('Title', 'Body text');
        $slug = LandingPageSlug::create('page-title');
        $title = LandingPageTitle::create('Page title');
        $subject->setLandingPage(
            $slug,
            $title,
            'Page description',
            SubjectLandingPageStatus::CONCEPT,
            new SubjectContentTree(title: '', intro: '', children: [$node], outro: ''),
        );

        self::assertSame($slug, $subject->getLandingPageSlug());
        self::assertSame($title, $subject->getLandingPageTitle());
        self::assertSame('Page description', $subject->getLandingPageDescription());
        self::assertSame(SubjectLandingPageStatus::CONCEPT, $subject->getLandingPageStatus());
        self::assertNotNull($subject->getLandingPagePreviewToken());
        self::assertEquals(
            new SubjectContentTree(title: '', intro: '', children: [$node], outro: ''),
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
            new SubjectContentTree(title: '', intro: '', children: [$root], outro: ''),
        );

        $tree = $subject->getLandingPageContentTree();
        self::assertInstanceOf(SubjectContentTree::class, $tree);
        self::assertSame('L1', $tree->children[0]->title);
        self::assertSame('L2', $tree->children[0]->children[0]->title);
        self::assertSame('L3', $tree->children[0]->children[0]->children[0]->title);
    }

    public function testTokenStableAcrossConceptToConceptSave(): void
    {
        $subject = new Subject();
        $subject->setLandingPage(
            LandingPageSlug::create('concept-to-concept'),
            LandingPageTitle::create('T'),
            'D',
            SubjectLandingPageStatus::CONCEPT,
            new SubjectContentTree(title: '', intro: '', children: [], outro: ''),
        );

        $firstToken = $subject->getLandingPagePreviewToken();
        self::assertNotNull($firstToken);

        $subject->setLandingPage(
            LandingPageSlug::create('concept-to-concept-updated'),
            LandingPageTitle::create('T2'),
            'D2',
            SubjectLandingPageStatus::CONCEPT,
            new SubjectContentTree(title: '', intro: '', children: [], outro: ''),
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
            new SubjectContentTree(title: '', intro: '', children: [], outro: ''),
        );

        $firstToken = $subject->getLandingPagePreviewToken();
        self::assertNotNull($firstToken);

        $subject->setLandingPage(
            LandingPageSlug::create('concept-to-published'),
            LandingPageTitle::create('T'),
            'D',
            SubjectLandingPageStatus::PUBLISHED,
            new SubjectContentTree(title: '', intro: '', children: [], outro: ''),
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
            new SubjectContentTree(title: '', intro: '', children: [], outro: ''),
        );

        self::assertNull($subject->getLandingPagePreviewToken());
    }
}
