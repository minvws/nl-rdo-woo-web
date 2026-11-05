<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Inventory;

use Doctrine\Common\Collections\ArrayCollection;
use Mockery;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReportProcessRun;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Service\Import\ImportError;
use Shared\Service\Inventory\DocumentComparator;
use Shared\Service\Inventory\DocumentMetadata;
use Shared\Service\Inventory\InventoryChangeset;
use Shared\Service\Inventory\InventoryComparator;
use Shared\Service\Inventory\Progress\RunProgress;
use Shared\Service\Inventory\Reader\InventoryReaderInterface;
use Shared\Service\Inventory\Reader\InventoryReadItem;
use Shared\Tests\Unit\Domain\Upload\IterableToGenerator;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DocumentNumber;

use function sprintf;

class InventoryComparatorTest extends UnitTestCase
{
    use IterableToGenerator;

    public function testDetermineChangesetFailsWhenDocumentExistsInAnotherDossier(): void
    {
        $rowIndex = $this->getFaker()->numberBetween(1, 100);
        $publicationContext = $this->getFaker()->publicationContext();
        $documentId = $this->getFaker()->documentId();
        $documentNumber = DocumentNumber::fromPublicationContextAndDocumentId($publicationContext, $documentId);

        $wooDecision = Mockery::mock(WooDecision::class);

        $documentMetadata = Mockery::mock(DocumentMetadata::class);
        $documentMetadata->expects('getPublicationContext')->andReturn($publicationContext);
        $documentMetadata->expects('getId')->andReturn($documentId);

        $inventoryReader = Mockery::mock(InventoryReaderInterface::class);
        $inventoryReader
            ->expects('getDocumentMetadataGenerator')
            ->with($wooDecision)
            ->andReturn($this->iterableToGenerator([new InventoryReadItem($documentMetadata, $rowIndex, null)]));

        $document = Mockery::mock(Document::class);
        $document->expects('getDossiers')->andReturn(new ArrayCollection());
        $document->expects('getDocumentId')->andReturn($documentId);
        $document->expects('getDocumentNumber')->andReturn($documentNumber);

        $documentRepository = Mockery::mock(DocumentRepository::class);
        $documentRepository->expects('getAllDocumentNumbersForDossier')
            ->with($wooDecision)
            ->andReturn([$documentNumber]);
        $documentRepository
            ->expects('findByDocumentNumber')
            ->with(Mockery::on(static function (DocumentNumber $value) use ($documentNumber): bool {
                return $value->toString() === $documentNumber->toString();
            }))
            ->andReturn($document);

        $documentComparator = Mockery::mock(DocumentComparator::class);
        $documentComparator->expects('needsUpdate')
            ->with($wooDecision, $document, $documentMetadata)
            ->andReturnFalse();

        $productionReportProcessRun = Mockery::mock(ProductionReportProcessRun::class);
        $productionReportProcessRun->expects('getDossier')->andReturn($wooDecision);
        $productionReportProcessRun
            ->expects('addRowError')
            ->with($rowIndex, Mockery::on(static function (ImportError $importError) use ($documentId): bool {
                return $importError->message === sprintf('Document %s already exists in another dossier', $documentId->toString())
                    && $importError->translationKey === 'publication.dossier.error.document_already_exists'
                    && $importError->placeholders === ['{document_id}' => $documentId->toString()];
            }));

        $runProgress = Mockery::mock(RunProgress::class);
        $runProgress->expects('update')->with($rowIndex);

        $inventoryComparator = new InventoryComparator($documentRepository, $documentComparator);
        $changeset = $inventoryComparator->determineChangeset($productionReportProcessRun, $inventoryReader, $runProgress);

        self::assertSame(InventoryChangeset::UNCHANGED, $changeset->getStatus($documentNumber));
    }

    public function testDetermineChangesetFailsWhenDocumentIsMissingFromInventory(): void
    {
        $documentNumber = $this->getFaker()->documentNumber();

        $wooDecision = Mockery::mock(WooDecision::class);
        $wooDecision->expects('getStatus')->andReturn(DossierStatus::PUBLISHED);

        $inventoryReader = Mockery::mock(InventoryReaderInterface::class);
        $inventoryReader
            ->expects('getDocumentMetadataGenerator')
            ->with($wooDecision)
            ->andReturn($this->iterableToGenerator([]));

        $documentRepository = Mockery::mock(DocumentRepository::class);
        $documentRepository->expects('getAllDocumentNumbersForDossier')
            ->with($wooDecision)
            ->andReturn([$documentNumber]);

        $productionReportProcessRun = Mockery::mock(ProductionReportProcessRun::class);
        $productionReportProcessRun->expects('getDossier')->andReturn($wooDecision);
        $productionReportProcessRun
            ->expects('addGenericError')
            ->with(Mockery::on(static function (ImportError $importError) use ($documentNumber): bool {
                return $importError->message === sprintf('Missing document %s in the inventory', $documentNumber->toString())
                    && $importError->translationKey === 'publication.dossier.error.missing_document'
                    && $importError->placeholders === ['{documentNumber}' => $documentNumber->toString()];
            }));

        $inventoryComparator = new InventoryComparator($documentRepository, Mockery::mock(DocumentComparator::class));
        $changeset = $inventoryComparator->determineChangeset(
            $productionReportProcessRun,
            $inventoryReader,
            Mockery::mock(RunProgress::class),
        );

        self::assertSame(InventoryChangeset::DELETED, $changeset->getStatus($documentNumber));
    }
}
