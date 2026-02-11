<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Inventory\Sanitizer\DataProvider;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry\InquiryInventory;
use Shared\Service\Inventory\Sanitizer\DataProvider\InquiryInventoryDataProvider;
use Shared\Tests\Unit\UnitTestCase;

class InquiryInventoryDataProviderTest extends UnitTestCase
{
    private Inquiry&MockInterface $inquiry;
    private Document&MockInterface $docA;
    private Document&MockInterface $docB;
    private InquiryInventoryDataProvider $dataProvider;

    protected function setUp(): void
    {
        $this->inquiry = Mockery::mock(Inquiry::class);
        $this->docA = Mockery::mock(Document::class);
        $this->docB = Mockery::mock(Document::class);

        $this->dataProvider = new InquiryInventoryDataProvider(
            $this->inquiry,
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
        $inventory = Mockery::mock(InquiryInventory::class);

        $this->inquiry->expects('getInventory')->andReturn($inventory);

        self::assertSame($inventory, $this->dataProvider->getInventoryEntity());
    }

    public function testGetInventoryCreatesNewInventoryIfNoneExists(): void
    {
        $this->inquiry->expects('getInventory')->andReturnNull();

        self::assertSame(
            $this->inquiry,
            $this->dataProvider->getInventoryEntity()->getInquiry(),
        );
    }

    public function testGetFilename(): void
    {
        $this->inquiry->expects('getCasenr')->andReturn('foo123');

        self::assertEquals(
            'inventarislijst-foo123',
            $this->dataProvider->getFilename(),
        );
    }
}
