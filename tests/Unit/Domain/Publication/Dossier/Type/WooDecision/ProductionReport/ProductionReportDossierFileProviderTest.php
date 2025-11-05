<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\ProductionReport;

use App\Domain\Publication\Dossier\FileProvider\DossierFileNotFoundException;
use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReport;
use App\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReportDossierFileProvider;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

final class ProductionReportDossierFileProviderTest extends UnitTestCase
{
    private ProductionReportDossierFileProvider $provider;

    protected function setUp(): void
    {
        $this->provider = new ProductionReportDossierFileProvider();

        parent::setUp();
    }

    public function testGetEntityForPublicUseThrowsException(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('getId')->andReturn(Uuid::v6());

        $this->expectException(DossierFileNotFoundException::class);

        $this->provider->getEntityForPublicUse($dossier, '');
    }

    public function testGetEntityForAdminUseThrowsExceptionForDossierTypeMismatch(): void
    {
        $dossier = \Mockery::mock(Covenant::class);
        $dossier->shouldReceive('getId')->andReturn(Uuid::v6());

        $this->expectException(DossierFileNotFoundException::class);

        $this->provider->getEntityForAdminUse($dossier, '');
    }

    public function testGetEntityForAdminUseThrowsExceptionWhenWooDecisionHasNoProductionReport(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('getId')->andReturn(Uuid::v6());
        $dossier->shouldReceive('getProductionReport')->andReturnNull();

        $this->expectException(DossierFileNotFoundException::class);

        $this->provider->getEntityForAdminUse($dossier, '');
    }

    public function testGetEntityForAdminUse(): void
    {
        $productionReport = \Mockery::mock(ProductionReport::class);

        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('getId')->andReturn(Uuid::v6());
        $dossier->shouldReceive('getProductionReport')->andReturn($productionReport);

        $idInput = '55ae5de9-55f4-3420-b50b-5cde6e07fc5a';

        self::assertSame(
            $productionReport,
            $this->provider->getEntityForAdminUse($dossier, $idInput),
        );
    }
}
