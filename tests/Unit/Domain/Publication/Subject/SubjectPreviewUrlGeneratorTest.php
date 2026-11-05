<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Subject;

use Mockery;
use Shared\Controller\Public\SubjectLandingPageController;
use Shared\Domain\Publication\PublicUrlGenerator;
use Shared\Domain\Publication\Subject\LandingPageTitle;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Domain\Publication\Subject\SubjectContentTree;
use Shared\Domain\Publication\Subject\SubjectContentTreeStatus;
use Shared\Domain\Publication\Subject\SubjectLandingPageStatus;
use Shared\Domain\Publication\Subject\SubjectPreviewUrlGenerator;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\Url;

class SubjectPreviewUrlGeneratorTest extends UnitTestCase
{
    public function testGeneratesPreviewUrlForConceptWithToken(): void
    {
        $subject = new Subject();
        $subject->setLandingPage(
            $this->getFaker()->landingPageSlug(),
            LandingPageTitle::create($this->getFaker()->sentence()),
            $this->getFaker()->sentence(),
            SubjectLandingPageStatus::CONCEPT,
            new SubjectContentTree(title: '', intro: '', children: [], outro: ''),
            SubjectContentTreeStatus::CONCEPT,
        );

        $previewToken = $subject->getLandingPagePreviewToken();
        $url = Url::create($this->getFaker()->url());

        $publicUrlGenerator = Mockery::mock(PublicUrlGenerator::class);
        $publicUrlGenerator
            ->expects('buildUrlFromRoute')
            ->with(SubjectLandingPageController::ROUTE_NAME_SUBJECT_LANDING_PAGE_PREVIEW, [
                'id' => $subject->getId()->toRfc4122(),
                'previewToken' => $previewToken->toRfc4122(),
            ])
            ->andReturn($url);

        self::assertSame($url, new SubjectPreviewUrlGenerator($publicUrlGenerator)->generatePreviewUrl($subject));
    }

    public function testReturnsNullForPublishedStatus(): void
    {
        $subject = new Subject();
        $subject->setLandingPage(
            $this->getFaker()->landingPageSlug(),
            LandingPageTitle::create($this->getFaker()->sentence()),
            $this->getFaker()->sentence(),
            SubjectLandingPageStatus::PUBLISHED,
            new SubjectContentTree(title: '', intro: '', children: [], outro: ''),
            SubjectContentTreeStatus::CONCEPT,
        );

        $generator = new SubjectPreviewUrlGenerator(Mockery::mock(PublicUrlGenerator::class));

        self::assertNull($generator->generatePreviewUrl($subject));
    }
}
