<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Entity\ProductionReport;
use Mockery\Adapter\Phpunit\MockeryTestCase;

final class ProductionReportTest extends MockeryTestCase
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
