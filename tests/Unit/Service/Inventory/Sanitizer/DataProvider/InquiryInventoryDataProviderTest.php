<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Inventory\Sanitizer\DataProvider;

use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use App\Domain\Publication\Dossier\Type\WooDecision\Inquiry\InquiryInventory;
use App\Service\Inventory\Sanitizer\DataProvider\InquiryInventoryDataProvider;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class InquiryInventoryDataProviderTest extends MockeryTestCase
{
    private Inquiry&MockInterface $inquiry;
    private Document&MockInterface $docA;
    private Document&MockInterface $docB;
    private InquiryInventoryDataProvider $dataProvider;

    public function setUp(): void
    {
        $this->inquiry = \Mockery::mock(Inquiry::class);
        $this->docA = \Mockery::mock(Document::class);
        $this->docB = \Mockery::mock(Document::class);

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
        $inventory = \Mockery::mock(InquiryInventory::class);

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
