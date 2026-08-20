<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Subject;

use Doctrine\Common\Collections\Collection;
use Mockery;
use Shared\Domain\Organisation\Organisation;
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
            'Landing page title',
            'Landing page description',
            SubjectLandingPageStatus::PUBLISHED,
            $contentTree,
        );

        self::assertSame($subject, $result);
        self::assertSame('Landing page title', $subject->getLandingPageTitle());
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
            'Landing page title',
            'Landing page description',
            SubjectLandingPageStatus::CONCEPT,
            [],
        );

        $previewToken = $subject->getLandingPagePreviewToken();
        self::assertInstanceOf(Uuid::class, $previewToken);

        $subject->setLandingPage(
            'Updated landing page title',
            'Updated landing page description',
            SubjectLandingPageStatus::CONCEPT,
            [],
        );

        self::assertSame($previewToken, $subject->getLandingPagePreviewToken());
    }
}
