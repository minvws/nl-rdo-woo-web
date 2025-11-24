<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Type\InvestigationReport;

use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;
use Shared\Domain\Publication\Dossier\Type\DossierType;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportMainDocument;

final class InvestigationReportTest extends TestCase
{
    public function testGetType(): void
    {
        $dossier = new InvestigationReport();
        self::assertEquals(DossierType::INVESTIGATION_REPORT, $dossier->getType());
    }

    public function testGetAndSetMainDocument(): void
    {
        $dossier = new InvestigationReport();
        self::assertNull($dossier->getMainDocument());

        $document = \Mockery::mock(InvestigationReportMainDocument::class);
        $dossier->setMainDocument($document);

        self::assertEquals($document, $dossier->getMainDocument());
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
