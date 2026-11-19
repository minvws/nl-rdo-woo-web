<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Inventory;

use Doctrine\Common\Collections\ArrayCollection;
use Mockery;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReportProcessRun;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Exception\Import\ImportException;
use Shared\Exception\Import\InvalidFamilyIdException;
use Shared\Exception\Import\UnexpectedRowException;
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
use Symfony\Component\Uid\Uuid;

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

        $inventoryComparator = new InventoryComparator($documentRepository, $documentComparator, Mockery::mock(LoggerInterface::class));
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

        $inventoryComparator = new InventoryComparator(
            $documentRepository,
            Mockery::mock(DocumentComparator::class),
            Mockery::mock(LoggerInterface::class),
        );
        $changeset = $inventoryComparator->determineChangeset(
            $productionReportProcessRun,
            $inventoryReader,
            Mockery::mock(RunProgress::class),
        );

        self::assertSame(InventoryChangeset::DELETED, $changeset->getStatus($documentNumber));
    }

    public function testDetermineChangesetLogsTheCauseOfAnUnexpectedRowError(): void
    {
        $rowIndex = $this->getFaker()->numberBetween(1, 100);
        $runId = Uuid::v6();
        $unexpectedRowException = new UnexpectedRowException(new RuntimeException('foo'));

        $wooDecision = Mockery::mock(WooDecision::class);

        $inventoryReader = Mockery::mock(InventoryReaderInterface::class);
        $inventoryReader
            ->expects('getDocumentMetadataGenerator')
            ->with($wooDecision)
            ->andReturn($this->iterableToGenerator([new InventoryReadItem(null, $rowIndex, $unexpectedRowException)]));

        $documentRepository = Mockery::mock(DocumentRepository::class);
        $documentRepository->expects('getAllDocumentNumbersForDossier')->with($wooDecision)->andReturn([]);

        $productionReportProcessRun = Mockery::mock(ProductionReportProcessRun::class);
        $productionReportProcessRun->expects('getDossier')->andReturn($wooDecision);
        $productionReportProcessRun->expects('getId')->andReturn($runId);
        $productionReportProcessRun->expects('addRowError')->with($rowIndex, Mockery::isEqual(ImportError::fromException($unexpectedRowException)));

        $logger = Mockery::mock(LoggerInterface::class);
        $logger->expects('error')->with(Mockery::type('string'), [
            'runUuid' => $runId,
            'rowIndex' => $rowIndex,
            'exception' => $unexpectedRowException,
        ]);

        $runProgress = Mockery::mock(RunProgress::class);
        $runProgress->expects('update')->with($rowIndex);

        $inventoryComparator = new InventoryComparator($documentRepository, Mockery::mock(DocumentComparator::class), $logger);
        $inventoryComparator->determineChangeset($productionReportProcessRun, $inventoryReader, $runProgress);
    }

    public function testDetermineChangesetDoesNotLogExpectedRowErrors(): void
    {
        $rowIndex = $this->getFaker()->numberBetween(1, 100);
        $invalidFamilyIdException = new InvalidFamilyIdException($rowIndex);

        $wooDecision = Mockery::mock(WooDecision::class);

        $inventoryReader = Mockery::mock(InventoryReaderInterface::class);
        $inventoryReader
            ->expects('getDocumentMetadataGenerator')
            ->with($wooDecision)
            ->andReturn($this->iterableToGenerator([new InventoryReadItem(null, $rowIndex, $invalidFamilyIdException)]));

        $documentRepository = Mockery::mock(DocumentRepository::class);
        $documentRepository->expects('getAllDocumentNumbersForDossier')->with($wooDecision)->andReturn([]);

        $productionReportProcessRun = Mockery::mock(ProductionReportProcessRun::class);
        $productionReportProcessRun->expects('getDossier')->andReturn($wooDecision);
        $productionReportProcessRun->expects('addRowError')->with($rowIndex, Mockery::isEqual(ImportError::fromException($invalidFamilyIdException)));

        $logger = Mockery::mock(LoggerInterface::class);
        $logger->expects('error')->never();

        $runProgress = Mockery::mock(RunProgress::class);
        $runProgress->expects('update')->with($rowIndex);

        $inventoryComparator = new InventoryComparator($documentRepository, Mockery::mock(DocumentComparator::class), $logger);
        $inventoryComparator->determineChangeset($productionReportProcessRun, $inventoryReader, $runProgress);
    }

    public function testDetermineChangesetAddsRowErrorAndContinuesWhenComparingARowFailsUnexpectedly(): void
    {
        $runId = Uuid::v6();
        $publicationContext = $this->getFaker()->publicationContext();
        $failingDocumentId = $this->getFaker()->unique()->documentId();
        $documentId = $this->getFaker()->unique()->documentId();
        $documentNumber = DocumentNumber::fromPublicationContextAndDocumentId($publicationContext, $documentId);

        $wooDecision = Mockery::mock(WooDecision::class);

        $failingDocumentMetadata = Mockery::mock(DocumentMetadata::class);
        $failingDocumentMetadata->expects('getPublicationContext')->andReturn($publicationContext);
        $failingDocumentMetadata->expects('getId')->andReturn($failingDocumentId);

        $documentMetadata = Mockery::mock(DocumentMetadata::class);
        $documentMetadata->expects('getPublicationContext')->andReturn($publicationContext);
        $documentMetadata->expects('getId')->andReturn($documentId);

        $inventoryReader = Mockery::mock(InventoryReaderInterface::class);
        $inventoryReader
            ->expects('getDocumentMetadataGenerator')
            ->with($wooDecision)
            ->andReturn($this->iterableToGenerator([
                new InventoryReadItem($failingDocumentMetadata, 2, null),
                new InventoryReadItem($documentMetadata, 3, null),
            ]));

        $exception = new RuntimeException('foo');

        $documentRepository = Mockery::mock(DocumentRepository::class);
        $documentRepository->expects('getAllDocumentNumbersForDossier')->with($wooDecision)->andReturn([]);
        $documentRepository->expects('findByDocumentNumber')->twice()->andReturnUsing(
            static function (DocumentNumber $value) use ($documentNumber, $exception): null {
                if ($value->equalTo($documentNumber)) {
                    return null;
                }

                throw $exception;
            },
        );

        $productionReportProcessRun = Mockery::mock(ProductionReportProcessRun::class);
        $productionReportProcessRun->expects('getDossier')->andReturn($wooDecision);
        $productionReportProcessRun->expects('getId')->andReturn($runId);
        $productionReportProcessRun
            ->expects('addRowError')
            ->with(2, Mockery::on(static function (ImportError $importError): bool {
                return $importError->translationKey === 'publication.dossier.error.generic_document_row_exception';
            }));

        $logger = Mockery::mock(LoggerInterface::class);
        $logger->expects('error')->with(Mockery::type('string'), Mockery::on(
            static fn (array $context): bool => $context['rowIndex'] === 2 && $context['exception'] instanceof ImportException
                && $context['exception']->getPrevious() === $exception,
        ));

        $runProgress = Mockery::mock(RunProgress::class);
        $runProgress->expects('update')->twice();

        $inventoryComparator = new InventoryComparator($documentRepository, Mockery::mock(DocumentComparator::class), $logger);
        $changeset = $inventoryComparator->determineChangeset($productionReportProcessRun, $inventoryReader, $runProgress);

        self::assertSame(InventoryChangeset::ADDED, $changeset->getStatus($documentNumber));
    }
}
