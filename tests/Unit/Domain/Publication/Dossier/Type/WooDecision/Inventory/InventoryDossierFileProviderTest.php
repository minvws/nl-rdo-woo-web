<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\Inventory;

use App\Domain\Publication\Dossier\FileProvider\DossierFileNotFoundException;
use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Publication\Dossier\Type\WooDecision\Inventory\Inventory;
use App\Domain\Publication\Dossier\Type\WooDecision\Inventory\InventoryDossierFileProvider;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

final class InventoryDossierFileProviderTest extends UnitTestCase
{
    private InventoryDossierFileProvider $provider;

    public function setUp(): void
    {
        $this->provider = new InventoryDossierFileProvider();

        parent::setUp();
    }

    public function testGetEntityForPublicUseThrowsExceptionForDossierTypeMismatch(): void
    {
        $dossier = \Mockery::mock(Covenant::class);
        $dossier->shouldReceive('getId')->andReturn(Uuid::v6());

        $this->expectException(DossierFileNotFoundException::class);

        $this->provider->getEntityForPublicUse($dossier, '');
    }

    public function testGetEntityForAdminUseThrowsExceptionWhenWooDecisionHasNoInventory(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('getId')->andReturn(Uuid::v6());
        $dossier->shouldReceive('getInventory')->andReturnNull();

        $this->expectException(DossierFileNotFoundException::class);

        $this->provider->getEntityForPublicUse($dossier, '');
    }

    public function testGetEntityForPublicUse(): void
    {
        $inventory = \Mockery::mock(Inventory::class);

        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('getId')->andReturn(Uuid::v6());
        $dossier->shouldReceive('getInventory')->andReturn($inventory);

        self::assertSame(
            $inventory,
            $this->provider->getEntityForPublicUse($dossier, ''),
        );
    }

    public function testGetEntityForAdminUse(): void
    {
        $inventory = \Mockery::mock(Inventory::class);

        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('getId')->andReturn(Uuid::v6());
        $dossier->shouldReceive('getInventory')->andReturn($inventory);

        self::assertSame(
            $inventory,
            $this->provider->getEntityForAdminUse($dossier, ''),
        );
    }
}
