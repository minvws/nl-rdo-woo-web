<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Inventory\Sanitizer\DataProvider;

use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\Inventory\Inventory;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Service\Inventory\Sanitizer\DataProvider\WooDecisionInventoryDataProvider;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class WooDecisionInventoryDataProviderTest extends MockeryTestCase
{
    private WooDecision&MockInterface $wooDecision;
    private Document&MockInterface $docA;
    private Document&MockInterface $docB;
    private WooDecisionInventoryDataProvider $dataProvider;

    public function setUp(): void
    {
        $this->wooDecision = \Mockery::mock(WooDecision::class);
        $this->docA = \Mockery::mock(Document::class);
        $this->docB = \Mockery::mock(Document::class);

        $this->dataProvider = new WooDecisionInventoryDataProvider(
            $this->wooDecision,
            [$this->docA, $this->docB],
        );

        parent::setUp();
    }

    public function testGetDocuments(): void
    {
        self::assertEquals(
            [$this->docA, $this->docB],
            $this->dataProvider->getDocuments(),
        );
    }

    public function testGetInventoryUsesExistingInventory(): void
    {
        $inventory = \Mockery::mock(Inventory::class);

        $this->wooDecision->expects('getInventory')->andReturn($inventory);

        self::assertSame($inventory, $this->dataProvider->getInventoryEntity());
    }

    public function testGetInventoryCreatesNewInventoryIfNoneExists(): void
    {
        $this->wooDecision->expects('getInventory')->andReturnNull();

        self::assertSame(
            $this->wooDecision,
            $this->dataProvider->getInventoryEntity()->getDossier(),
        );
    }

    public function testGetFilename(): void
    {
        $this->wooDecision->expects('getDossierNr')->andReturn('foo123');

        self::assertEquals(
            'inventarislijst-foo123',
            $this->dataProvider->getFilename(),
        );
    }
}
