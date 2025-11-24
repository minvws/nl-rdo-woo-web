<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\ProductionReport;

use Shared\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReport;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Tests\Unit\UnitTestCase;

final class ProductionReportTest extends UnitTestCase
{
    public function testSetAndGetDossier(): void
    {
        $productionReport = new ProductionReport();

        $wooDecision = \Mockery::mock(WooDecision::class);
        $wooDecision->expects('setProductionReport')->with($productionReport);

        $productionReport->setDossier($wooDecision);

        self::assertSame($wooDecision, $productionReport->getDossier());
    }
}
