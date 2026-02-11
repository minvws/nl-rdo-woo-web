<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use Mockery;
use PHPUnit\Framework\TestCase;
use Shared\Domain\Department\Department;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Subject\Subject;

final class AbstractDossierTest extends TestCase
{
    public function testGetAndSetInternalReference(): void
    {
        $covenant = new Covenant();
        self::assertEquals('', $covenant->getInternalReference());

        $covenant->setInternalReference($ref = 'foo');

        self::assertEquals($ref, $covenant->getInternalReference());
    }

    public function testGetAndSetSubject(): void
    {
        $covenant = new Covenant();
        self::assertNull($covenant->getSubject());

        $covenant->setSubject($subject = Mockery::mock(Subject::class));

        self::assertEquals($subject, $covenant->getSubject());
    }

    public function testGetAndSetSummary(): void
    {
        $covenant = new Covenant();
        self::assertEquals('', $covenant->getSummary());

        $covenant->setSummary($summary = 'foo bar');

        self::assertEquals($summary, $covenant->getSummary());
    }

    public function testAddAndRemoveDepartment(): void
    {
        $covenant = new Covenant();
        self::assertCount(0, $covenant->getDepartments());

        $covenant->addDepartment($department = Mockery::mock(Department::class));
        self::assertCount(1, $covenant->getDepartments());

        $covenant->removeDepartment($department);
        self::assertCount(0, $covenant->getDepartments());
    }

    public function testHasFuturePublicationDateReturnsTrueForToday(): void
    {
        $covenant = new Covenant();
        $covenant->setPublicationDate(new DateTimeImmutable());

        self::assertTrue($covenant->hasFuturePublicationDate());
    }

    public function testHasFuturePublicationDateReturnsFalseForYesterday(): void
    {
        $covenant = new Covenant();
        $covenant->setPublicationDate(new DateTimeImmutable('-1 day'));

        self::assertFalse($covenant->hasFuturePublicationDate());
    }

    public function testHasFuturePublicationDateReturnsTrueForTomorrow(): void
    {
        $covenant = new Covenant();
        $covenant->setPublicationDate(new DateTimeImmutable('+1 day'));

        self::assertTrue($covenant->hasFuturePublicationDate());
    }

    public function testSetStatusToPublishedUpdatesPublicationDateOnlyOnce(): void
    {
        CarbonImmutable::setTestNow('2024-04-30 09:42:11');
        $testDate = CarbonImmutable::now();

        $covenant = new Covenant();
        $covenant->setStatus(DossierStatus::PUBLISHED);
        $publicationDate = $covenant->getPublicationDate();

        self::assertEquals($testDate, $publicationDate);

        // This new testdate should not be applied when setting the status to 'published' a second time
        Carbon::setTestNow('2024-05-21 19:23:45');
        $covenant->setStatus(DossierStatus::PUBLISHED);
        self::assertEquals($testDate, $covenant->getPublicationDate());
    }
}
