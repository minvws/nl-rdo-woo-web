<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\InvestigationReport;

use App\Domain\Publication\Dossier\Type\DossierType;
use App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport;
use App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportDocument;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

final class InvestigationReportTest extends TestCase
{
    public function testGetType(): void
    {
        $dossier = new InvestigationReport();
        self::assertEquals(DossierType::INVESTIGATION_REPORT, $dossier->getType());
    }

    public function testGetAndSetDocument(): void
    {
        $dossier = new InvestigationReport();
        self::assertNull($dossier->getDocument());

        $document = \Mockery::mock(InvestigationReportDocument::class);
        $dossier->setDocument($document);

        self::assertEquals($document, $dossier->getDocument());
    }

    public function testSetDateFromSetsDateTo(): void
    {
        $dossier = new InvestigationReport();

        $date = new CarbonImmutable();

        $dossier->setDateFrom($date);

        self::assertEquals($date, $dossier->getDateFrom());
        self::assertEquals($date, $dossier->getDateTo());
    }
}
