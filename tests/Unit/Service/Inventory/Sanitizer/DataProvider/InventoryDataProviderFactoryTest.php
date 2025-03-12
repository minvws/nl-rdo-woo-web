<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Inventory\Sanitizer\DataProvider;

use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use App\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use App\Domain\Publication\Dossier\Type\WooDecision\Inquiry\InquiryInventory;
use App\Domain\Publication\Dossier\Type\WooDecision\Inventory\Inventory;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Service\Inventory\Sanitizer\DataProvider\InventoryDataProviderFactory;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class InventoryDataProviderFactoryTest extends MockeryTestCase
{
    private DocumentRepository&MockInterface $documentRepository;
    private Document&MockInterface $docA;
    private Document&MockInterface $docB;
    private InventoryDataProviderFactory $factory;

    public function setUp(): void
    {
        $this->documentRepository = \Mockery::mock(DocumentRepository::class);
        $this->docA = \Mockery::mock(Document::class);
        $this->docB = \Mockery::mock(Document::class);

        $this->factory = new InventoryDataProviderFactory(
            $this->documentRepository,
        );

        parent::setUp();
    }

    public function testForWooDecision(): void
    {
        $wooDecision = \Mockery::mock(WooDecision::class);
        $wooDecision->expects('getInventory')->andReturn($inventory = \Mockery::mock(Inventory::class));

        $this->documentRepository
            ->expects('getAllDossierDocumentsWithDossiers')
            ->with($wooDecision)
            ->andReturn([$this->docA, $this->docB]);

        $dataProvider = $this->factory->forWooDecision($wooDecision);

        self::assertSame(
            $inventory,
            $dataProvider->getInventoryEntity(),
        );

        self::assertEquals(
            [$this->docA, $this->docB],
            $dataProvider->getDocuments(),
        );
    }

    public function testForInquiry(): void
    {
        $inquiry = \Mockery::mock(Inquiry::class);
        $inquiry->expects('getInventory')->andReturn($inventory = \Mockery::mock(InquiryInventory::class));

        $this->documentRepository
            ->expects('getAllInquiryDocumentsWithDossiers')
            ->with($inquiry)
            ->andReturn([$this->docA, $this->docB]);

        $dataProvider = $this->factory->forInquiry($inquiry);

        self::assertSame(
            $inventory,
            $dataProvider->getInventoryEntity(),
        );

        self::assertEquals(
            [$this->docA, $this->docB],
            $dataProvider->getDocuments(),
        );
    }
}
