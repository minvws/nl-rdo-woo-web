<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Subject;

use LogicException;
use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Subject\LandingPageSlug;
use Shared\Domain\Publication\Subject\LandingPageTitle;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Domain\Publication\Subject\SubjectLandingPageStatus;
use Shared\Domain\Publication\Subject\SubjectPreviewUrlGenerator;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

class SubjectPreviewUrlGeneratorTest extends UnitTestCase
{
    public function testGeneratesPreviewUrlForConceptWithToken(): void
    {
        $generator = new SubjectPreviewUrlGenerator('https://example.com');

        $subject = new Subject();
        $subject->setLandingPage(
            LandingPageSlug::create('preview'),
            LandingPageTitle::create('T'),
            'D',
            SubjectLandingPageStatus::CONCEPT,
            [],
        );

        $url = $generator->generatePreviewUrl($subject);

        self::assertNotNull($url);
        self::assertStringStartsWith(
            'https://example.com/onderwerp/' . $subject->getId()->toRfc4122() . '/preview/',
            $url,
        );
    }

    public function testReturnsNullForPublishedStatus(): void
    {
        $generator = new SubjectPreviewUrlGenerator('https://example.com');

        $subject = new Subject();
        $subject->setLandingPage(
            LandingPageSlug::create('published'),
            LandingPageTitle::create('T'),
            'D',
            SubjectLandingPageStatus::PUBLISHED,
            [],
        );

        self::assertNull($generator->generatePreviewUrl($subject));
    }

    public function testReturnsNullWhenStatusIsNull(): void
    {
        $generator = new SubjectPreviewUrlGenerator('https://example.com');

        $subject = new Subject();
        self::assertNull($subject->getLandingPageStatus());

        self::assertNull($generator->generatePreviewUrl($subject));
    }

    public function testThrowsLogicExceptionForConceptWithNullToken(): void
    {
        $generator = new SubjectPreviewUrlGenerator('https://example.com');

        /** @var Subject&MockInterface $subject */
        $subject = Mockery::mock(Subject::class);
        $subject->expects('getLandingPageStatus')->andReturn(SubjectLandingPageStatus::CONCEPT);
        $subject->expects('getLandingPagePreviewToken')->andReturn(null);
        $subject->expects('getId')->andReturn(Uuid::v6());

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/no preview token/');

        $generator->generatePreviewUrl($subject);
    }

    public function testBaseUrlTrailingSlashNormalized(): void
    {
        $generator = new SubjectPreviewUrlGenerator('https://example.com/');

        $subject = new Subject();
        $subject->setLandingPage(
            LandingPageSlug::create('normalized'),
            LandingPageTitle::create('T'),
            'D',
            SubjectLandingPageStatus::CONCEPT,
            [],
        );

        $url = $generator->generatePreviewUrl($subject);

        self::assertNotNull($url);
        self::assertStringNotContainsString('//onderwerp', $url);
    }
}
