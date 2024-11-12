<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\AnnualReport;

use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReport;
use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportMainDocument;
use App\Domain\Publication\Dossier\Type\DossierType;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

final class AnnualReportTest extends TestCase
{
    public function testGetType(): void
    {
        $annualReport = new AnnualReport();
        self::assertEquals(DossierType::ANNUAL_REPORT, $annualReport->getType());
    }

    public function testGetAndSetMainDocument(): void
    {
        $annualReport = new AnnualReport();
        self::assertNull($annualReport->getMainDocument());

        $document = \Mockery::mock(AnnualReportMainDocument::class);
        $annualReport->setMainDocument($document);

        self::assertEquals($document, $annualReport->getMainDocument());
    }

    public function testSetDateFromSetsDateRangeToWholeYear(): void
    {
        $annualReport = new AnnualReport();

        $date = new CarbonImmutable();

        $annualReport->setDateFrom($date);

        self::assertEquals($date->firstOfYear(), $annualReport->getDateFrom());
        self::assertEquals($date->lastOfYear(), $annualReport->getDateTo());
    }
}
