<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Inventory\Sanitizer\DataProvider;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry\InquiryInventory;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inventory\Inventory;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Service\Inventory\Sanitizer\DataProvider\InventoryDataProviderFactory;
use Shared\Service\Inventory\Sanitizer\DossierInventoryDocumentUrlStrategy;
use Shared\Service\Inventory\Sanitizer\InquiryInventoryDocumentUrlStrategy;
use Shared\Tests\Unit\UnitTestCase;

class InventoryDataProviderFactoryTest extends UnitTestCase
{
    private DocumentRepository&MockInterface $documentRepository;
    private Document&MockInterface $docA;
    private Document&MockInterface $docB;
    private DossierInventoryDocumentUrlStrategy&MockInterface $dossierUrlStrategy;
    private InquiryInventoryDocumentUrlStrategy&MockInterface $inquiryUrlStrategy;
    private InventoryDataProviderFactory $factory;

    protected function setUp(): void
    {
        $this->documentRepository = Mockery::mock(DocumentRepository::class);
        $this->docA = Mockery::mock(Document::class);
        $this->docB = Mockery::mock(Document::class);
        $this->dossierUrlStrategy = Mockery::mock(DossierInventoryDocumentUrlStrategy::class);
        $this->inquiryUrlStrategy = Mockery::mock(InquiryInventoryDocumentUrlStrategy::class);

        $this->factory = new InventoryDataProviderFactory(
            $this->documentRepository,
            $this->dossierUrlStrategy,
            $this->inquiryUrlStrategy,
        );

        parent::setUp();
    }

    public function testForWooDecision(): void
    {
        $wooDecision = Mockery::mock(WooDecision::class);
        $wooDecision->expects('getInventory')->andReturn($inventory = Mockery::mock(Inventory::class));

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
        self::assertSame($this->dossierUrlStrategy, $dataProvider->getDocumentUrlStrategy());
    }

    public function testForInquiry(): void
    {
        $inquiry = Mockery::mock(Inquiry::class);
        $inquiry->expects('getInventory')->andReturn($inventory = Mockery::mock(InquiryInventory::class));

        $this->documentRepository
            ->expects('getPublicInquiryDocumentsWithDossiers')
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
        self::assertSame($this->inquiryUrlStrategy, $dataProvider->getDocumentUrlStrategy());
    }
}
