<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Type\Disposition;

use Carbon\CarbonImmutable;
use Mockery;
use PHPUnit\Framework\TestCase;
use Shared\Domain\Publication\Dossier\Type\Disposition\Disposition;
use Shared\Domain\Publication\Dossier\Type\Disposition\DispositionMainDocument;
use Shared\Domain\Publication\Dossier\Type\DossierType;

final class DispositionReportTest extends TestCase
{
    public function testGetType(): void
    {
        $dossier = new Disposition();
        self::assertEquals(DossierType::DISPOSITION, $dossier->getType());
    }

    public function testGetAndSetMainDocument(): void
    {
        $dossier = new Disposition();
        self::assertNull($dossier->getMainDocument());

        $document = Mockery::mock(DispositionMainDocument::class);
        $dossier->setMainDocument($document);

        self::assertEquals($document, $dossier->getMainDocument());
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
