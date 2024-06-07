<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\Disposition;

use App\Domain\Publication\Dossier\Type\Disposition\Disposition;
use App\Domain\Publication\Dossier\Type\Disposition\DispositionDocument;
use App\Domain\Publication\Dossier\Type\DossierType;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

final class DispositionReportTest extends TestCase
{
    public function testGetType(): void
    {
        $dossier = new Disposition();
        self::assertEquals(DossierType::DISPOSITION, $dossier->getType());
    }

    public function testGetAndSetDocument(): void
    {
        $dossier = new Disposition();
        self::assertNull($dossier->getDocument());

        $document = \Mockery::mock(DispositionDocument::class);
        $dossier->setDocument($document);

        self::assertEquals($document, $dossier->getDocument());
    }

    public function testSetDateFromSetsDateTo(): void
    {
        $dossier = new Disposition();

        $date = new CarbonImmutable();

        $dossier->setDateFrom($date);

        self::assertEquals($date, $dossier->getDateFrom());
        self::assertEquals($date, $dossier->getDateTo());
    }
}
