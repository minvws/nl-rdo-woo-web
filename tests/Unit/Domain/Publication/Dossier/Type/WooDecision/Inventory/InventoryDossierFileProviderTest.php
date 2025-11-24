<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\Inventory;

use Shared\Domain\Publication\Dossier\FileProvider\DossierFileNotFoundException;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inventory\Inventory;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inventory\InventoryDossierFileProvider;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

final class InventoryDossierFileProviderTest extends UnitTestCase
{
    private InventoryDossierFileProvider $provider;

    protected function setUp(): void
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
