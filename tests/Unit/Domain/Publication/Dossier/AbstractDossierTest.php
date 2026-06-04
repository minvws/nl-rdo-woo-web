<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier;

use Mockery;
use PHPUnit\Framework\TestCase;
use Shared\Domain\Department\Department;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Subject\Subject;
use Shared\ValueObject\PlainDate;

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

    public function testHasFuturePublicationDateReturnsFalseIfNull(): void
    {
        $covenant = new Covenant();
        $covenant->setPublicationDate(null);

        self::assertFalse($covenant->hasFuturePublicationDate());
    }

    public function testHasFuturePublicationDateReturnsFalseForToday(): void
    {
        $covenant = new Covenant();
        $covenant->setPublicationDate(PlainDate::today());

        self::assertFalse($covenant->hasFuturePublicationDate());
    }

    public function testHasFuturePublicationDateReturnsFalseForYesterday(): void
    {
        $covenant = new Covenant();
        $covenant->setPublicationDate(PlainDate::today()->subDays(1));

        self::assertFalse($covenant->hasFuturePublicationDate());
    }

    public function testHasFuturePublicationDateReturnsTrueForTomorrow(): void
    {
        $covenant = new Covenant();
        $covenant->setPublicationDate(PlainDate::today()->addDays(1));

        self::assertTrue($covenant->hasFuturePublicationDate());
    }

    public function testSetStatusToPublishedSetsPublicationDate(): void
    {
        $covenant = new Covenant();
        $covenant->setStatus(DossierStatus::PUBLISHED);
        $publicationDate = $covenant->getPublicationDate();

        self::assertEquals(PlainDate::today(), $publicationDate);
    }

    public function testSetStatusToPublishedOverwritesExistingPublicationDate(): void
    {
        $covenant = new Covenant();
        $covenant->setPublicationDate(PlainDate::create('2000-01-01'));
        $covenant->setStatus(DossierStatus::PUBLISHED);
        $publicationDate = $covenant->getPublicationDate();

        self::assertEquals(PlainDate::today(), $publicationDate);
    }

    public function testSetStatusToPublishedOverwritesExistingPublicationDateOnlyOnce(): void
    {
        $covenant = new Covenant();
        // this sets the publicationDate to today()
        $covenant->setStatus(DossierStatus::PUBLISHED);

        // now set a custom publicationDate and set status to published again
        $covenant->setPublicationDate(PlainDate::create('2000-01-01'));
        $covenant->setStatus(DossierStatus::PUBLISHED);

        $publicationDate = $covenant->getPublicationDate();

        self::assertEquals(PlainDate::create('2000-01-01'), $publicationDate);
    }
}
