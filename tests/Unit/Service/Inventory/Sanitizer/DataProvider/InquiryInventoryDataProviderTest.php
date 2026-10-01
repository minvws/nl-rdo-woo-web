<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Inventory\Sanitizer\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;
use InvalidArgumentException;
use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry\InquiryInventory;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Service\Inventory\Sanitizer\DataProvider\InquiryInventoryDataProvider;
use Shared\Service\Inventory\Sanitizer\InventoryDocumentUrlStrategyInterface;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\PlainDate;

class InquiryInventoryDataProviderTest extends UnitTestCase
{
    private Inquiry&MockInterface $inquiry;
    private Document&MockInterface $docA;
    private Document&MockInterface $docB;
    private InventoryDocumentUrlStrategyInterface&MockInterface $urlStrategy;
    private InquiryInventoryDataProvider $dataProvider;

    protected function setUp(): void
    {
        $this->inquiry = Mockery::mock(Inquiry::class);
        $this->docA = Mockery::mock(Document::class);
        $this->docB = Mockery::mock(Document::class);
        $this->urlStrategy = Mockery::mock(InventoryDocumentUrlStrategyInterface::class);

        $this->dataProvider = new InquiryInventoryDataProvider(
            $this->inquiry,
            [$this->docA, $this->docB],
            $this->urlStrategy,
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

    public function testGetDossierForDocumentUsesSingleDossier(): void
    {
        $singleDossier = Mockery::mock(WooDecision::class);
        $this->docA->expects('getDossiers')->andReturn(new ArrayCollection([$singleDossier]));

        self::assertSame(
            $singleDossier,
            $this->dataProvider->getDossierForDocument($this->docA),
        );
    }

    public function testGetDossierForDocumentUsesDossierFromInquiry(): void
    {
        $unrelatedDossier = Mockery::mock(WooDecision::class);
        $inquiryDossier = Mockery::mock(WooDecision::class);

        $inquiryDossier->expects('getStatus')->andReturn(DossierStatus::PUBLISHED);
        $inquiryDossier->expects('getDecisionDate')->andReturn(PlainDate::create('2024-01-01'));

        $this->inquiry->expects('getDossiers')->andReturn(new ArrayCollection([$inquiryDossier]));
        $this->docA->expects('getDossiers')->andReturn(
            new ArrayCollection([$unrelatedDossier, $inquiryDossier]),
        );

        self::assertSame(
            $inquiryDossier,
            $this->dataProvider->getDossierForDocument($this->docA),
        );
    }

    public function testGetDossierForDocumentUsesPublishedDossierWithOldestDecisionDate(): void
    {
        $newerPublishedDossier = Mockery::mock(WooDecision::class);
        $newerPublishedDossier->expects('getStatus')->andReturn(DossierStatus::PUBLISHED);
        $newerPublishedDossier->expects('getDecisionDate')->andReturn(PlainDate::create('2024-02-01'));

        $olderPublishedDossier = Mockery::mock(WooDecision::class);
        $olderPublishedDossier->expects('getStatus')->andReturn(DossierStatus::PUBLISHED);
        $olderPublishedDossier->expects('getDecisionDate')->andReturn(PlainDate::create('2024-01-01'));

        $previewDossier = Mockery::mock(WooDecision::class);
        $previewDossier->expects('getStatus')->andReturn(DossierStatus::PREVIEW);

        $this->inquiry->expects('getDossiers')->andReturn(new ArrayCollection([
            $newerPublishedDossier,
            $previewDossier,
            $olderPublishedDossier,
        ]));
        $this->docA->expects('getDossiers')->andReturn(new ArrayCollection([
            $newerPublishedDossier,
            $previewDossier,
            $olderPublishedDossier,
        ]));

        self::assertSame(
            $olderPublishedDossier,
            $this->dataProvider->getDossierForDocument($this->docA),
        );
    }

    public function testGetDossierForDocumentUsesFirstMatchingDossierWhenNoneIsPublished(): void
    {
        $firstMatchingDossier = Mockery::mock(WooDecision::class);
        $firstMatchingDossier->expects('getStatus')->andReturn(DossierStatus::CONCEPT);

        $secondMatchingDossier = Mockery::mock(WooDecision::class);
        $secondMatchingDossier->expects('getStatus')->andReturn(DossierStatus::PREVIEW);

        $this->inquiry->expects('getDossiers')->andReturn(new ArrayCollection([
            $firstMatchingDossier,
            $secondMatchingDossier,
        ]));
        $this->docA->expects('getDossiers')->andReturn(new ArrayCollection([
            $firstMatchingDossier,
            $secondMatchingDossier,
        ]));

        self::assertSame(
            $firstMatchingDossier,
            $this->dataProvider->getDossierForDocument($this->docA),
        );
    }

    public function testGetDossierForDocumentFailsWhenPublishedDossierHasNoDecisionDate(): void
    {
        $publishedDossier = Mockery::mock(WooDecision::class);
        $publishedDossier->expects('getStatus')->andReturn(DossierStatus::PUBLISHED);
        $publishedDossier->expects('getDecisionDate')->andReturnNull();

        $unrelatedDossier = Mockery::mock(WooDecision::class);

        $this->inquiry->expects('getDossiers')->andReturn(new ArrayCollection([$publishedDossier]));
        $this->docA->expects('getDossiers')->andReturn(
            new ArrayCollection([$publishedDossier, $unrelatedDossier]),
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIs('A published WooDecision must have a decision date.');

        $this->dataProvider->getDossierForDocument($this->docA);
    }

    public function testGetDocumentUrlStrategy(): void
    {
        self::assertSame($this->urlStrategy, $this->dataProvider->getDocumentUrlStrategy());
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
        $this->inquiry->expects('getInquiryNumber')->andReturn('foo123');

        self::assertEquals(
            'inventarislijst-foo123',
            $this->dataProvider->getFilename(),
        );
    }
}
