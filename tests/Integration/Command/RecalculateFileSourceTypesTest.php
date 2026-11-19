<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Command;

use Doctrine\ORM\EntityManagerInterface;
use Generator;
use Mockery;
use RuntimeException;
use Shared\Command\RecalculateFileSourceTypes;
use Shared\Domain\Ingest\IngestDispatcher;
use Shared\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment;
use Shared\Domain\Publication\Dossier\Type\Disposition\DispositionMainDocument;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry\InquiryInventory;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use Shared\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReport;
use Shared\Domain\Publication\EntityWithFileInfo;
use Shared\Domain\Publication\SourceType;
use Shared\Service\Inquiry\InquiryNumbers;
use Shared\Service\Inventory\DocumentMetadata;
use Shared\Service\Inventory\InventoryService;
use Shared\Service\Inventory\Reader\InventoryReaderInterface;
use Shared\Service\Inventory\Reader\InventoryReadItem;
use Shared\Tests\Factory\DocumentFactory;
use Shared\Tests\Factory\InquiryFactory;
use Shared\Tests\Factory\ProductionReportFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\Disposition\DispositionAttachmentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\Disposition\DispositionMainDocumentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Shared\Tests\Integration\SharedWebTestCase;
use Shared\ValueObject\DocumentNumber;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;
use Webmozart\Assert\Assert;

use function sprintf;

final class RecalculateFileSourceTypesTest extends SharedWebTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();

        $this->entityManager = self::fromContainer(EntityManagerInterface::class);
    }

    public function testRecalculatesOnlyDocRecordsAndReindexesChangedDocuments(): void
    {
        $textDocument = DocumentFactory::createOne();
        $textDocument->getFileInfo()
            ->setMimetype('text/plain')
            ->setSourceType(SourceType::DOC);

        $wordDocument = DocumentFactory::createOne();
        $wordDocument->getFileInfo()
            ->setMimetype('application/msword')
            ->setSourceType(SourceType::DOC);

        $otherSourceTypeDocument = DocumentFactory::createOne();
        $otherSourceTypeDocument->getFileInfo()
            ->setMimetype('text/plain')
            ->setSourceType(SourceType::PDF);

        $documentWithoutMimetype = DocumentFactory::createOne();
        $documentWithoutMimetype->getFileInfo()
            ->setMimetype(null)
            ->setSourceType(SourceType::DOC);

        $pdfDocument = DocumentFactory::createOne();
        $pdfDocument->getFileInfo()
            ->setMimetype('application/pdf')
            ->setSourceType(SourceType::DOC);

        $textDocumentId = $textDocument->getId();
        $wordDocumentId = $wordDocument->getId();
        $otherSourceTypeDocumentId = $otherSourceTypeDocument->getId();
        $documentWithoutMimetypeId = $documentWithoutMimetype->getId();
        $pdfDocumentId = $pdfDocument->getId();
        $this->entityManager->flush();

        $ingestDispatcher = Mockery::mock(IngestDispatcher::class);
        $ingestDispatcher->expects('dispatchIngestMetadataOnlyCommandForEntity')
            ->with($textDocument, false);
        self::getContainer()->set(IngestDispatcher::class, $ingestDispatcher);

        $commandTester = $this->executeCommand();

        self::assertSame(Command::SUCCESS, $commandTester->getStatusCode());

        $this->entityManager->clear();

        $updatedTextDocument = $this->entityManager->find(Document::class, $textDocumentId);
        self::assertInstanceOf(Document::class, $updatedTextDocument);
        self::assertSame(SourceType::TXT, $updatedTextDocument->getFileInfo()->getSourceType());

        $unchangedWordDocument = $this->entityManager->find(Document::class, $wordDocumentId);
        self::assertInstanceOf(Document::class, $unchangedWordDocument);
        self::assertSame(SourceType::DOC, $unchangedWordDocument->getFileInfo()->getSourceType());

        $unchangedOtherSourceTypeDocument = $this->entityManager->find(Document::class, $otherSourceTypeDocumentId);
        self::assertInstanceOf(Document::class, $unchangedOtherSourceTypeDocument);
        self::assertSame(SourceType::PDF, $unchangedOtherSourceTypeDocument->getFileInfo()->getSourceType());

        $unchangedDocumentWithoutMimetype = $this->entityManager->find(Document::class, $documentWithoutMimetypeId);
        self::assertInstanceOf(Document::class, $unchangedDocumentWithoutMimetype);
        self::assertSame(SourceType::DOC, $unchangedDocumentWithoutMimetype->getFileInfo()->getSourceType());

        $unchangedPdfDocument = $this->entityManager->find(Document::class, $pdfDocumentId);
        self::assertInstanceOf(Document::class, $unchangedPdfDocument);
        self::assertSame(SourceType::DOC, $unchangedPdfDocument->getFileInfo()->getSourceType());
    }

    public function testRestoresUnknownDocumentSourceTypesFromProductionReport(): void
    {
        $publicationContext = $this->getFaker()->publicationContext();
        $documentId = $this->getFaker()->documentId();
        /** @var SourceType $sourceType */
        $sourceType = $this->getFaker()->randomElement([SourceType::PDF, SourceType::SPREADSHEET, SourceType::PRESENTATION, SourceType::EMAIL]);

        $dossier = WooDecisionFactory::createOne();
        $productionReport = ProductionReportFactory::createOne(['dossier' => $dossier]);

        $unknownDocument = DocumentFactory::createOne([
            'documentId' => $documentId,
            'documentNumber' => DocumentNumber::fromPublicationContextAndDocumentId($publicationContext, $documentId),
            'dossiers' => [$dossier],
        ]);
        $unknownDocument->getFileInfo()->setSourceType(SourceType::UNKNOWN);

        $unknownDocumentId = $unknownDocument->getId();
        $productionReportId = $productionReport->getId();
        $this->entityManager->flush();
        $this->entityManager->clear();

        $documentMetadata = new DocumentMetadata(
            date: null,
            filename: $this->getFaker()->word(),
            familyId: null,
            sourceType: $sourceType,
            grounds: [],
            id: $documentId,
            judgement: Judgement::PUBLIC,
            period: null,
            threadId: null,
            inquiryNumbers: InquiryNumbers::empty(),
            suspended: false,
            links: [],
            remark: null,
            publicationContext: $publicationContext,
            refersTo: [],
        );

        $inventoryReader = Mockery::mock(InventoryReaderInterface::class);
        $inventoryReader->expects('getDocumentMetadataGenerator')
            ->andReturn((static function () use ($documentMetadata): Generator {
                yield new InventoryReadItem($documentMetadata, 1, null);
            })());

        $inventoryService = Mockery::mock(InventoryService::class);
        $inventoryService->expects('getReader')
            ->with(Mockery::on(static function (ProductionReport $value) use ($productionReportId): bool {
                return $value->getId()->equals($productionReportId);
            }))
            ->andReturn($inventoryReader);
        self::getContainer()->set(InventoryService::class, $inventoryService);

        $ingestDispatcher = Mockery::mock(IngestDispatcher::class);
        $ingestDispatcher->expects('dispatchIngestMetadataOnlyCommandForEntity')
            ->with(Mockery::on(static function (Document $value) use ($unknownDocumentId): bool {
                return $value->getId()->equals($unknownDocumentId);
            }), false);
        self::getContainer()->set(IngestDispatcher::class, $ingestDispatcher);

        $commandTester = $this->executeCommand();

        self::assertSame(Command::SUCCESS, $commandTester->getStatusCode());

        $this->entityManager->clear();

        $restoredDocument = $this->entityManager->find(Document::class, $unknownDocumentId);
        self::assertInstanceOf(Document::class, $restoredDocument);
        self::assertSame($sourceType, $restoredDocument->getFileInfo()->getSourceType());

        self::assertStringContainsString(
            'Restored 1 document source types from production reports.',
            $commandTester->getDisplay(),
        );
    }

    public function testRestoresDocumentChangedFromDocToOtherSourceType(): void
    {
        $publicationContext = $this->getFaker()->publicationContext();
        $documentId = $this->getFaker()->documentId();

        $dossier = WooDecisionFactory::createOne();
        $productionReport = ProductionReportFactory::createOne(['dossier' => $dossier]);

        $changedDocument = DocumentFactory::createOne([
            'documentId' => $documentId,
            'documentNumber' => DocumentNumber::fromPublicationContextAndDocumentId($publicationContext, $documentId),
            'dossiers' => [$dossier],
        ]);
        $changedDocument->getFileInfo()
            ->setMimetype('application/pdf')
            ->setSourceType(SourceType::PDF);

        $changedDocumentId = $changedDocument->getId();
        $productionReportId = $productionReport->getId();
        $this->entityManager->flush();
        $this->entityManager->clear();

        $documentMetadata = new DocumentMetadata(
            date: null,
            filename: $this->getFaker()->word(),
            familyId: null,
            sourceType: SourceType::DOC,
            grounds: [],
            id: $documentId,
            judgement: Judgement::PUBLIC,
            period: null,
            threadId: null,
            inquiryNumbers: InquiryNumbers::empty(),
            suspended: false,
            links: [],
            remark: null,
            publicationContext: $publicationContext,
            refersTo: [],
        );

        $inventoryReader = Mockery::mock(InventoryReaderInterface::class);
        $inventoryReader->expects('getDocumentMetadataGenerator')
            ->andReturn((static function () use ($documentMetadata): Generator {
                yield new InventoryReadItem($documentMetadata, 1, null);
            })());

        $inventoryService = Mockery::mock(InventoryService::class);
        $inventoryService->expects('getReader')
            ->with(Mockery::on(static function (ProductionReport $value) use ($productionReportId): bool {
                return $value->getId()->equals($productionReportId);
            }))
            ->andReturn($inventoryReader);
        self::getContainer()->set(InventoryService::class, $inventoryService);

        $ingestDispatcher = Mockery::mock(IngestDispatcher::class);
        $ingestDispatcher->expects('dispatchIngestMetadataOnlyCommandForEntity')
            ->with(Mockery::on(static function (Document $value) use ($changedDocumentId): bool {
                return $value->getId()->equals($changedDocumentId);
            }), false);
        self::getContainer()->set(IngestDispatcher::class, $ingestDispatcher);

        $commandTester = $this->executeCommand();

        self::assertSame(Command::SUCCESS, $commandTester->getStatusCode());

        $this->entityManager->clear();

        $restoredDocument = $this->entityManager->find(Document::class, $changedDocumentId);
        self::assertInstanceOf(Document::class, $restoredDocument);
        self::assertSame(SourceType::DOC, $restoredDocument->getFileInfo()->getSourceType());

        self::assertStringContainsString(
            'Restored 1 document source types from production reports.',
            $commandTester->getDisplay(),
        );
    }

    public function testRestoresDocDocumentWithTextMimetypeAsTxt(): void
    {
        $publicationContext = $this->getFaker()->publicationContext();
        $documentId = $this->getFaker()->documentId();

        $dossier = WooDecisionFactory::createOne();
        $productionReport = ProductionReportFactory::createOne(['dossier' => $dossier]);

        $unknownDocument = DocumentFactory::createOne([
            'documentId' => $documentId,
            'documentNumber' => DocumentNumber::fromPublicationContextAndDocumentId($publicationContext, $documentId),
            'dossiers' => [$dossier],
        ]);
        $unknownDocument->getFileInfo()
            ->setMimetype('text/plain')
            ->setSourceType(SourceType::UNKNOWN);

        $unknownDocumentId = $unknownDocument->getId();
        $productionReportId = $productionReport->getId();
        $this->entityManager->flush();
        $this->entityManager->clear();

        $documentMetadata = new DocumentMetadata(
            date: null,
            filename: $this->getFaker()->word(),
            familyId: null,
            sourceType: SourceType::DOC,
            grounds: [],
            id: $documentId,
            judgement: Judgement::PUBLIC,
            period: null,
            threadId: null,
            inquiryNumbers: InquiryNumbers::empty(),
            suspended: false,
            links: [],
            remark: null,
            publicationContext: $publicationContext,
            refersTo: [],
        );

        $inventoryReader = Mockery::mock(InventoryReaderInterface::class);
        $inventoryReader->expects('getDocumentMetadataGenerator')
            ->andReturn((static function () use ($documentMetadata): Generator {
                yield new InventoryReadItem($documentMetadata, 1, null);
            })());

        $inventoryService = Mockery::mock(InventoryService::class);
        $inventoryService->expects('getReader')
            ->with(Mockery::on(static function (ProductionReport $value) use ($productionReportId): bool {
                return $value->getId()->equals($productionReportId);
            }))
            ->andReturn($inventoryReader);
        self::getContainer()->set(InventoryService::class, $inventoryService);

        $ingestDispatcher = Mockery::mock(IngestDispatcher::class);
        $ingestDispatcher->expects('dispatchIngestMetadataOnlyCommandForEntity')
            ->with(Mockery::on(static function (Document $value) use ($unknownDocumentId): bool {
                return $value->getId()->equals($unknownDocumentId);
            }), false);
        self::getContainer()->set(IngestDispatcher::class, $ingestDispatcher);

        $commandTester = $this->executeCommand();

        self::assertSame(Command::SUCCESS, $commandTester->getStatusCode());

        $this->entityManager->clear();

        $recalculatedDocument = $this->entityManager->find(Document::class, $unknownDocumentId);
        self::assertInstanceOf(Document::class, $recalculatedDocument);
        self::assertSame(SourceType::TXT, $recalculatedDocument->getFileInfo()->getSourceType());
    }

    public function testKeepsUnknownWhenProductionReportSourceTypeIsUnknown(): void
    {
        $publicationContext = $this->getFaker()->publicationContext();
        $documentId = $this->getFaker()->documentId();

        $dossier = WooDecisionFactory::createOne();
        $productionReport = ProductionReportFactory::createOne(['dossier' => $dossier]);

        $unknownDocument = DocumentFactory::createOne([
            'documentId' => $documentId,
            'documentNumber' => DocumentNumber::fromPublicationContextAndDocumentId($publicationContext, $documentId),
            'dossiers' => [$dossier],
        ]);
        $unknownDocument->getFileInfo()->setSourceType(SourceType::UNKNOWN);

        $unknownDocumentId = $unknownDocument->getId();
        $productionReportId = $productionReport->getId();
        $this->entityManager->flush();
        $this->entityManager->clear();

        $documentMetadata = new DocumentMetadata(
            date: null,
            filename: $this->getFaker()->word(),
            familyId: null,
            sourceType: SourceType::UNKNOWN,
            grounds: [],
            id: $documentId,
            judgement: Judgement::PUBLIC,
            period: null,
            threadId: null,
            inquiryNumbers: InquiryNumbers::empty(),
            suspended: false,
            links: [],
            remark: null,
            publicationContext: $publicationContext,
            refersTo: [],
        );

        $inventoryReader = Mockery::mock(InventoryReaderInterface::class);
        $inventoryReader->expects('getDocumentMetadataGenerator')
            ->andReturn((static function () use ($documentMetadata): Generator {
                yield new InventoryReadItem($documentMetadata, 1, null);
            })());

        $inventoryService = Mockery::mock(InventoryService::class);
        $inventoryService->expects('getReader')
            ->with(Mockery::on(static function (ProductionReport $value) use ($productionReportId): bool {
                return $value->getId()->equals($productionReportId);
            }))
            ->andReturn($inventoryReader);
        self::getContainer()->set(InventoryService::class, $inventoryService);

        $ingestDispatcher = Mockery::mock(IngestDispatcher::class);
        self::getContainer()->set(IngestDispatcher::class, $ingestDispatcher);

        $commandTester = $this->executeCommand();

        self::assertSame(Command::SUCCESS, $commandTester->getStatusCode());

        $this->entityManager->clear();

        $unchangedDocument = $this->entityManager->find(Document::class, $unknownDocumentId);
        self::assertInstanceOf(Document::class, $unchangedDocument);
        self::assertSame(SourceType::UNKNOWN, $unchangedDocument->getFileInfo()->getSourceType());

        self::assertStringContainsString(
            'Restored 0 document source types from production reports.',
            $commandTester->getDisplay(),
        );
    }

    public function testLeavesDocumentsMatchingProductionReportUntouched(): void
    {
        $publicationContext = $this->getFaker()->publicationContext();
        $unknownDocumentDocumentId = $this->getFaker()->documentId();
        $pdfDocumentDocumentId = $this->getFaker()->documentId();

        $dossier = WooDecisionFactory::createOne();
        $productionReport = ProductionReportFactory::createOne(['dossier' => $dossier]);

        $unknownDocument = DocumentFactory::createOne([
            'documentId' => $unknownDocumentDocumentId,
            'documentNumber' => DocumentNumber::fromPublicationContextAndDocumentId($publicationContext, $unknownDocumentDocumentId),
            'dossiers' => [$dossier],
        ]);
        $unknownDocument->getFileInfo()->setSourceType(SourceType::UNKNOWN);

        $pdfDocument = DocumentFactory::createOne([
            'documentId' => $pdfDocumentDocumentId,
            'documentNumber' => DocumentNumber::fromPublicationContextAndDocumentId($publicationContext, $pdfDocumentDocumentId),
            'dossiers' => [$dossier],
        ]);
        $pdfDocument->getFileInfo()->setSourceType(SourceType::PDF);

        $unknownDocumentId = $unknownDocument->getId();
        $pdfDocumentId = $pdfDocument->getId();
        $productionReportId = $productionReport->getId();
        $this->entityManager->flush();
        $this->entityManager->clear();

        $unknownDocumentMetadata = new DocumentMetadata(
            date: null,
            filename: $this->getFaker()->word(),
            familyId: null,
            sourceType: SourceType::SPREADSHEET,
            grounds: [],
            id: $unknownDocumentDocumentId,
            judgement: Judgement::PUBLIC,
            period: null,
            threadId: null,
            inquiryNumbers: InquiryNumbers::empty(),
            suspended: false,
            links: [],
            remark: null,
            publicationContext: $publicationContext,
            refersTo: [],
        );

        $pdfDocumentMetadata = new DocumentMetadata(
            date: null,
            filename: $this->getFaker()->word(),
            familyId: null,
            sourceType: SourceType::PDF,
            grounds: [],
            id: $pdfDocumentDocumentId,
            judgement: Judgement::PUBLIC,
            period: null,
            threadId: null,
            inquiryNumbers: InquiryNumbers::empty(),
            suspended: false,
            links: [],
            remark: null,
            publicationContext: $publicationContext,
            refersTo: [],
        );

        $inventoryReader = Mockery::mock(InventoryReaderInterface::class);
        $inventoryReader->expects('getDocumentMetadataGenerator')
            ->andReturn((static function () use ($unknownDocumentMetadata, $pdfDocumentMetadata): Generator {
                yield new InventoryReadItem($unknownDocumentMetadata, 1, null);
                yield new InventoryReadItem($pdfDocumentMetadata, 2, null);
            })());

        $inventoryService = Mockery::mock(InventoryService::class);
        $inventoryService->expects('getReader')
            ->with(Mockery::on(static function (ProductionReport $value) use ($productionReportId): bool {
                return $value->getId()->equals($productionReportId);
            }))
            ->andReturn($inventoryReader);
        self::getContainer()->set(InventoryService::class, $inventoryService);

        $ingestDispatcher = Mockery::mock(IngestDispatcher::class);
        $ingestDispatcher->expects('dispatchIngestMetadataOnlyCommandForEntity')
            ->with(Mockery::on(static function (Document $value) use ($unknownDocumentId): bool {
                return $value->getId()->equals($unknownDocumentId);
            }), false);
        self::getContainer()->set(IngestDispatcher::class, $ingestDispatcher);

        $commandTester = $this->executeCommand();

        self::assertSame(Command::SUCCESS, $commandTester->getStatusCode());

        $this->entityManager->clear();

        $restoredDocument = $this->entityManager->find(Document::class, $unknownDocumentId);
        self::assertInstanceOf(Document::class, $restoredDocument);
        self::assertSame(SourceType::SPREADSHEET, $restoredDocument->getFileInfo()->getSourceType());

        $unchangedPdfDocument = $this->entityManager->find(Document::class, $pdfDocumentId);
        self::assertInstanceOf(Document::class, $unchangedPdfDocument);
        self::assertSame(SourceType::PDF, $unchangedPdfDocument->getFileInfo()->getSourceType());
    }

    public function testSkipsProductionReportRowsWithoutDocumentMetadata(): void
    {
        $dossier = WooDecisionFactory::createOne();
        $productionReport = ProductionReportFactory::createOne(['dossier' => $dossier]);

        $unknownDocument = DocumentFactory::createOne(['dossiers' => [$dossier]]);
        $unknownDocument->getFileInfo()->setSourceType(SourceType::UNKNOWN);

        $unknownDocumentId = $unknownDocument->getId();
        $productionReportId = $productionReport->getId();
        $this->entityManager->flush();
        $this->entityManager->clear();

        $inventoryReader = Mockery::mock(InventoryReaderInterface::class);
        $inventoryReader->expects('getDocumentMetadataGenerator')
            ->andReturn((static function (): Generator {
                yield new InventoryReadItem(null, 1, null);
            })());

        $inventoryService = Mockery::mock(InventoryService::class);
        $inventoryService->expects('getReader')
            ->with(Mockery::on(static function (ProductionReport $value) use ($productionReportId): bool {
                return $value->getId()->equals($productionReportId);
            }))
            ->andReturn($inventoryReader);
        self::getContainer()->set(InventoryService::class, $inventoryService);

        $ingestDispatcher = Mockery::mock(IngestDispatcher::class);
        self::getContainer()->set(IngestDispatcher::class, $ingestDispatcher);

        $commandTester = $this->executeCommand();

        self::assertSame(Command::SUCCESS, $commandTester->getStatusCode());

        $this->entityManager->clear();

        $unchangedDocument = $this->entityManager->find(Document::class, $unknownDocumentId);
        self::assertInstanceOf(Document::class, $unchangedDocument);
        self::assertSame(SourceType::UNKNOWN, $unchangedDocument->getFileInfo()->getSourceType());
    }

    public function testSkipsDossiersWithoutProductionReport(): void
    {
        $dossier = WooDecisionFactory::createOne();

        $unknownDocument = DocumentFactory::createOne(['dossiers' => [$dossier]]);
        $unknownDocument->getFileInfo()->setSourceType(SourceType::UNKNOWN);

        $unknownDocumentId = $unknownDocument->getId();
        $this->entityManager->flush();

        $inventoryService = Mockery::mock(InventoryService::class);
        self::getContainer()->set(InventoryService::class, $inventoryService);

        $ingestDispatcher = Mockery::mock(IngestDispatcher::class);
        self::getContainer()->set(IngestDispatcher::class, $ingestDispatcher);

        $commandTester = $this->executeCommand();

        self::assertSame(Command::SUCCESS, $commandTester->getStatusCode());

        $this->entityManager->clear();

        $unchangedDocument = $this->entityManager->find(Document::class, $unknownDocumentId);
        self::assertInstanceOf(Document::class, $unchangedDocument);
        self::assertSame(SourceType::UNKNOWN, $unchangedDocument->getFileInfo()->getSourceType());
    }

    public function testReportsDossierWithUnreadableProductionReportAndFails(): void
    {
        $exceptionMessage = $this->getFaker()->sentence();

        $dossier = WooDecisionFactory::createOne();
        $productionReport = ProductionReportFactory::createOne(['dossier' => $dossier]);

        $unknownDocument = DocumentFactory::createOne(['dossiers' => [$dossier]]);
        $unknownDocument->getFileInfo()->setSourceType(SourceType::UNKNOWN);

        $dossierId = $dossier->getId();
        $unknownDocumentId = $unknownDocument->getId();
        $productionReportId = $productionReport->getId();
        $this->entityManager->flush();
        $this->entityManager->clear();

        $inventoryService = Mockery::mock(InventoryService::class);
        $inventoryService->expects('getReader')
            ->with(Mockery::on(static function (ProductionReport $value) use ($productionReportId): bool {
                return $value->getId()->equals($productionReportId);
            }))
            ->andThrow(new RuntimeException($exceptionMessage));
        self::getContainer()->set(InventoryService::class, $inventoryService);

        $ingestDispatcher = Mockery::mock(IngestDispatcher::class);
        self::getContainer()->set(IngestDispatcher::class, $ingestDispatcher);

        $commandTester = $this->executeCommand();

        self::assertSame(Command::FAILURE, $commandTester->getStatusCode());

        $this->entityManager->clear();

        $unchangedDocument = $this->entityManager->find(Document::class, $unknownDocumentId);
        self::assertInstanceOf(Document::class, $unchangedDocument);
        self::assertSame(SourceType::UNKNOWN, $unchangedDocument->getFileInfo()->getSourceType());

        self::assertStringContainsString(
            sprintf('Could not read the production report of dossier %s: %s', $dossierId->toRfc4122(), $exceptionMessage),
            $commandTester->getDisplay(),
        );
    }

    public function testDryRunDoesNotRestoreDocumentSourceTypes(): void
    {
        $publicationContext = $this->getFaker()->publicationContext();
        $documentId = $this->getFaker()->documentId();
        /** @var SourceType $sourceType */
        $sourceType = $this->getFaker()->randomElement([SourceType::PDF, SourceType::SPREADSHEET, SourceType::PRESENTATION, SourceType::EMAIL]);

        $dossier = WooDecisionFactory::createOne();
        $productionReport = ProductionReportFactory::createOne(['dossier' => $dossier]);

        $unknownDocument = DocumentFactory::createOne([
            'documentId' => $documentId,
            'documentNumber' => DocumentNumber::fromPublicationContextAndDocumentId($publicationContext, $documentId),
            'dossiers' => [$dossier],
        ]);
        $unknownDocument->getFileInfo()->setSourceType(SourceType::UNKNOWN);

        $unknownDocumentId = $unknownDocument->getId();
        $productionReportId = $productionReport->getId();
        $this->entityManager->flush();
        $this->entityManager->clear();

        $documentMetadata = new DocumentMetadata(
            date: null,
            filename: $this->getFaker()->word(),
            familyId: null,
            sourceType: $sourceType,
            grounds: [],
            id: $documentId,
            judgement: Judgement::PUBLIC,
            period: null,
            threadId: null,
            inquiryNumbers: InquiryNumbers::empty(),
            suspended: false,
            links: [],
            remark: null,
            publicationContext: $publicationContext,
            refersTo: [],
        );

        $inventoryReader = Mockery::mock(InventoryReaderInterface::class);
        $inventoryReader->expects('getDocumentMetadataGenerator')
            ->andReturn((static function () use ($documentMetadata): Generator {
                yield new InventoryReadItem($documentMetadata, 1, null);
            })());

        $inventoryService = Mockery::mock(InventoryService::class);
        $inventoryService->expects('getReader')
            ->with(Mockery::on(static function (ProductionReport $value) use ($productionReportId): bool {
                return $value->getId()->equals($productionReportId);
            }))
            ->andReturn($inventoryReader);
        self::getContainer()->set(InventoryService::class, $inventoryService);

        $ingestDispatcher = Mockery::mock(IngestDispatcher::class);
        self::getContainer()->set(IngestDispatcher::class, $ingestDispatcher);

        $commandTester = $this->executeCommand(dryRun: true);

        self::assertSame(Command::SUCCESS, $commandTester->getStatusCode());

        $this->entityManager->clear();

        $unchangedDocument = $this->entityManager->find(Document::class, $unknownDocumentId);
        self::assertInstanceOf(Document::class, $unchangedDocument);
        self::assertSame(SourceType::UNKNOWN, $unchangedDocument->getFileInfo()->getSourceType());

        self::assertStringContainsString(
            'Would restore 1 document source types from production reports.',
            $commandTester->getDisplay(),
        );
    }

    public function testDryRunCountsRestoredTxtDocumentOnlyOnce(): void
    {
        $publicationContext = $this->getFaker()->publicationContext();
        $documentId = $this->getFaker()->documentId();

        $dossier = WooDecisionFactory::createOne();
        $productionReport = ProductionReportFactory::createOne(['dossier' => $dossier]);

        $textDocument = DocumentFactory::createOne([
            'documentId' => $documentId,
            'documentNumber' => DocumentNumber::fromPublicationContextAndDocumentId($publicationContext, $documentId),
            'dossiers' => [$dossier],
        ]);
        $textDocument->getFileInfo()
            ->setMimetype('text/plain')
            ->setSourceType(SourceType::DOC);

        $textDocumentId = $textDocument->getId();
        $productionReportId = $productionReport->getId();
        $this->entityManager->flush();
        $this->entityManager->clear();

        $documentMetadata = new DocumentMetadata(
            date: null,
            filename: $this->getFaker()->word(),
            familyId: null,
            sourceType: SourceType::DOC,
            grounds: [],
            id: $documentId,
            judgement: Judgement::PUBLIC,
            period: null,
            threadId: null,
            inquiryNumbers: InquiryNumbers::empty(),
            suspended: false,
            links: [],
            remark: null,
            publicationContext: $publicationContext,
            refersTo: [],
        );

        $inventoryReader = Mockery::mock(InventoryReaderInterface::class);
        $inventoryReader->expects('getDocumentMetadataGenerator')
            ->andReturn((static function () use ($documentMetadata): Generator {
                yield new InventoryReadItem($documentMetadata, 1, null);
            })());

        $inventoryService = Mockery::mock(InventoryService::class);
        $inventoryService->expects('getReader')
            ->with(Mockery::on(static function (ProductionReport $value) use ($productionReportId): bool {
                return $value->getId()->equals($productionReportId);
            }))
            ->andReturn($inventoryReader);
        self::getContainer()->set(InventoryService::class, $inventoryService);

        $ingestDispatcher = Mockery::mock(IngestDispatcher::class);
        self::getContainer()->set(IngestDispatcher::class, $ingestDispatcher);

        $commandTester = $this->executeCommand(dryRun: true);

        self::assertSame(Command::SUCCESS, $commandTester->getStatusCode());

        $this->entityManager->clear();

        $unchangedDocument = $this->entityManager->find(Document::class, $textDocumentId);
        self::assertInstanceOf(Document::class, $unchangedDocument);
        self::assertSame(SourceType::DOC, $unchangedDocument->getFileInfo()->getSourceType());

        self::assertStringContainsString(
            'Would restore 1 document source types from production reports.',
            $commandTester->getDisplay(),
        );
        self::assertStringContainsString(
            'would update 0 source types',
            $commandTester->getDisplay(),
        );
    }

    public function testDryRunDoesNotPersistChangesOrDispatchReindex(): void
    {
        $document = DocumentFactory::createOne();
        $document->getFileInfo()
            ->setMimetype('text/plain')
            ->setSourceType(SourceType::DOC);

        $documentId = $document->getId();
        $this->entityManager->flush();

        $ingestDispatcher = Mockery::mock(IngestDispatcher::class);
        self::getContainer()->set(IngestDispatcher::class, $ingestDispatcher);

        $commandTester = $this->executeCommand(dryRun: true);

        self::assertSame(Command::SUCCESS, $commandTester->getStatusCode());

        $this->entityManager->clear();

        $unchangedDocument = $this->entityManager->find(Document::class, $documentId);
        self::assertInstanceOf(Document::class, $unchangedDocument);
        self::assertSame(SourceType::DOC, $unchangedDocument->getFileInfo()->getSourceType());

        self::assertStringContainsString(
            'would update 1 source types and would dispatch 1 Elasticsearch reindex messages',
            $commandTester->getDisplay(),
        );
    }

    public function testRestoresUnknownDocumentWithoutMimetypeToDoc(): void
    {
        $publicationContext = $this->getFaker()->publicationContext();
        $documentId = $this->getFaker()->documentId();

        $dossier = WooDecisionFactory::createOne();
        $productionReport = ProductionReportFactory::createOne(['dossier' => $dossier]);

        $unknownDocument = DocumentFactory::createOne([
            'documentId' => $documentId,
            'documentNumber' => DocumentNumber::fromPublicationContextAndDocumentId($publicationContext, $documentId),
            'dossiers' => [$dossier],
        ]);
        $unknownDocument->getFileInfo()
            ->setMimetype(null)
            ->setSourceType(SourceType::UNKNOWN);

        $unknownDocumentId = $unknownDocument->getId();
        $productionReportId = $productionReport->getId();
        $this->entityManager->flush();
        $this->entityManager->clear();

        $documentMetadata = new DocumentMetadata(
            date: null,
            filename: $this->getFaker()->word(),
            familyId: null,
            sourceType: SourceType::DOC,
            grounds: [],
            id: $documentId,
            judgement: Judgement::PUBLIC,
            period: null,
            threadId: null,
            inquiryNumbers: InquiryNumbers::empty(),
            suspended: false,
            links: [],
            remark: null,
            publicationContext: $publicationContext,
            refersTo: [],
        );

        $inventoryReader = Mockery::mock(InventoryReaderInterface::class);
        $inventoryReader->expects('getDocumentMetadataGenerator')
            ->andReturn((static function () use ($documentMetadata): Generator {
                yield new InventoryReadItem($documentMetadata, 1, null);
            })());

        $inventoryService = Mockery::mock(InventoryService::class);
        $inventoryService->expects('getReader')
            ->with(Mockery::on(static function (ProductionReport $value) use ($productionReportId): bool {
                return $value->getId()->equals($productionReportId);
            }))
            ->andReturn($inventoryReader);
        self::getContainer()->set(InventoryService::class, $inventoryService);

        $ingestDispatcher = Mockery::mock(IngestDispatcher::class);
        $ingestDispatcher->expects('dispatchIngestMetadataOnlyCommandForEntity')
            ->with(Mockery::on(static function (Document $value) use ($unknownDocumentId): bool {
                return $value->getId()->equals($unknownDocumentId);
            }), false);
        self::getContainer()->set(IngestDispatcher::class, $ingestDispatcher);

        $commandTester = $this->executeCommand();

        self::assertSame(Command::SUCCESS, $commandTester->getStatusCode());

        $this->entityManager->clear();

        $restoredDocument = $this->entityManager->find(Document::class, $unknownDocumentId);
        self::assertInstanceOf(Document::class, $restoredDocument);
        self::assertSame(SourceType::DOC, $restoredDocument->getFileInfo()->getSourceType());
    }

    public function testRestoresTextDocumentToProductionReportSourceTypeWhenReportIsNotDoc(): void
    {
        $publicationContext = $this->getFaker()->publicationContext();
        $documentId = $this->getFaker()->documentId();
        /** @var SourceType $sourceType */
        $sourceType = $this->getFaker()->randomElement([SourceType::PDF, SourceType::SPREADSHEET, SourceType::PRESENTATION, SourceType::EMAIL]);

        $dossier = WooDecisionFactory::createOne();
        $productionReport = ProductionReportFactory::createOne(['dossier' => $dossier]);

        $unknownDocument = DocumentFactory::createOne([
            'documentId' => $documentId,
            'documentNumber' => DocumentNumber::fromPublicationContextAndDocumentId($publicationContext, $documentId),
            'dossiers' => [$dossier],
        ]);
        $unknownDocument->getFileInfo()
            ->setMimetype('text/plain')
            ->setSourceType(SourceType::UNKNOWN);

        $unknownDocumentId = $unknownDocument->getId();
        $productionReportId = $productionReport->getId();
        $this->entityManager->flush();
        $this->entityManager->clear();

        $documentMetadata = new DocumentMetadata(
            date: null,
            filename: $this->getFaker()->word(),
            familyId: null,
            sourceType: $sourceType,
            grounds: [],
            id: $documentId,
            judgement: Judgement::PUBLIC,
            period: null,
            threadId: null,
            inquiryNumbers: InquiryNumbers::empty(),
            suspended: false,
            links: [],
            remark: null,
            publicationContext: $publicationContext,
            refersTo: [],
        );

        $inventoryReader = Mockery::mock(InventoryReaderInterface::class);
        $inventoryReader->expects('getDocumentMetadataGenerator')
            ->andReturn((static function () use ($documentMetadata): Generator {
                yield new InventoryReadItem($documentMetadata, 1, null);
            })());

        $inventoryService = Mockery::mock(InventoryService::class);
        $inventoryService->expects('getReader')
            ->with(Mockery::on(static function (ProductionReport $value) use ($productionReportId): bool {
                return $value->getId()->equals($productionReportId);
            }))
            ->andReturn($inventoryReader);
        self::getContainer()->set(InventoryService::class, $inventoryService);

        $ingestDispatcher = Mockery::mock(IngestDispatcher::class);
        $ingestDispatcher->expects('dispatchIngestMetadataOnlyCommandForEntity')
            ->with(Mockery::on(static function (Document $value) use ($unknownDocumentId): bool {
                return $value->getId()->equals($unknownDocumentId);
            }), false);
        self::getContainer()->set(IngestDispatcher::class, $ingestDispatcher);

        $commandTester = $this->executeCommand();

        self::assertSame(Command::SUCCESS, $commandTester->getStatusCode());

        $this->entityManager->clear();

        $restoredDocument = $this->entityManager->find(Document::class, $unknownDocumentId);
        self::assertInstanceOf(Document::class, $restoredDocument);
        self::assertSame($sourceType, $restoredDocument->getFileInfo()->getSourceType());
    }

    public function testLeavesTxtDocumentUntouchedWhenProductionReportSaysDoc(): void
    {
        $publicationContext = $this->getFaker()->publicationContext();
        $documentId = $this->getFaker()->documentId();

        $dossier = WooDecisionFactory::createOne();
        $productionReport = ProductionReportFactory::createOne(['dossier' => $dossier]);

        $textDocument = DocumentFactory::createOne([
            'documentId' => $documentId,
            'documentNumber' => DocumentNumber::fromPublicationContextAndDocumentId($publicationContext, $documentId),
            'dossiers' => [$dossier],
        ]);
        $textDocument->getFileInfo()
            ->setMimetype('text/plain')
            ->setSourceType(SourceType::TXT);

        $textDocumentId = $textDocument->getId();
        $productionReportId = $productionReport->getId();
        $this->entityManager->flush();
        $this->entityManager->clear();

        $documentMetadata = new DocumentMetadata(
            date: null,
            filename: $this->getFaker()->word(),
            familyId: null,
            sourceType: SourceType::DOC,
            grounds: [],
            id: $documentId,
            judgement: Judgement::PUBLIC,
            period: null,
            threadId: null,
            inquiryNumbers: InquiryNumbers::empty(),
            suspended: false,
            links: [],
            remark: null,
            publicationContext: $publicationContext,
            refersTo: [],
        );

        $inventoryReader = Mockery::mock(InventoryReaderInterface::class);
        $inventoryReader->expects('getDocumentMetadataGenerator')
            ->andReturn((static function () use ($documentMetadata): Generator {
                yield new InventoryReadItem($documentMetadata, 1, null);
            })());

        $inventoryService = Mockery::mock(InventoryService::class);
        $inventoryService->expects('getReader')
            ->with(Mockery::on(static function (ProductionReport $value) use ($productionReportId): bool {
                return $value->getId()->equals($productionReportId);
            }))
            ->andReturn($inventoryReader);
        self::getContainer()->set(InventoryService::class, $inventoryService);

        $ingestDispatcher = Mockery::mock(IngestDispatcher::class);
        self::getContainer()->set(IngestDispatcher::class, $ingestDispatcher);

        $commandTester = $this->executeCommand();

        self::assertSame(Command::SUCCESS, $commandTester->getStatusCode());

        $this->entityManager->clear();

        $unchangedDocument = $this->entityManager->find(Document::class, $textDocumentId);
        self::assertInstanceOf(Document::class, $unchangedDocument);
        self::assertSame(SourceType::TXT, $unchangedDocument->getFileInfo()->getSourceType());

        self::assertStringContainsString(
            'Restored 0 document source types from production reports.',
            $commandTester->getDisplay(),
        );
    }

    public function testDoesNotRecalculateDocumentMissingFromItsProductionReport(): void
    {
        $publicationContext = $this->getFaker()->publicationContext();
        $missingDocumentDocumentId = $this->getFaker()->documentId();
        $otherDocumentDocumentId = $this->getFaker()->documentId();

        $dossier = WooDecisionFactory::createOne();
        $productionReport = ProductionReportFactory::createOne(['dossier' => $dossier]);

        $missingDocument = DocumentFactory::createOne([
            'documentId' => $missingDocumentDocumentId,
            'documentNumber' => DocumentNumber::fromPublicationContextAndDocumentId($publicationContext, $missingDocumentDocumentId),
            'dossiers' => [$dossier],
        ]);
        $missingDocument->getFileInfo()
            ->setMimetype('text/plain')
            ->setSourceType(SourceType::DOC);

        $missingDocumentId = $missingDocument->getId();
        $productionReportId = $productionReport->getId();
        $this->entityManager->flush();
        $this->entityManager->clear();

        $otherDocumentMetadata = new DocumentMetadata(
            date: null,
            filename: $this->getFaker()->word(),
            familyId: null,
            sourceType: SourceType::PDF,
            grounds: [],
            id: $otherDocumentDocumentId,
            judgement: Judgement::PUBLIC,
            period: null,
            threadId: null,
            inquiryNumbers: InquiryNumbers::empty(),
            suspended: false,
            links: [],
            remark: null,
            publicationContext: $publicationContext,
            refersTo: [],
        );

        $inventoryReader = Mockery::mock(InventoryReaderInterface::class);
        $inventoryReader->expects('getDocumentMetadataGenerator')
            ->andReturn((static function () use ($otherDocumentMetadata): Generator {
                yield new InventoryReadItem($otherDocumentMetadata, 1, null);
            })());

        $inventoryService = Mockery::mock(InventoryService::class);
        $inventoryService->expects('getReader')
            ->with(Mockery::on(static function (ProductionReport $value) use ($productionReportId): bool {
                return $value->getId()->equals($productionReportId);
            }))
            ->andReturn($inventoryReader);
        self::getContainer()->set(InventoryService::class, $inventoryService);

        $ingestDispatcher = Mockery::mock(IngestDispatcher::class);
        self::getContainer()->set(IngestDispatcher::class, $ingestDispatcher);

        $commandTester = $this->executeCommand();

        self::assertSame(Command::SUCCESS, $commandTester->getStatusCode());

        $this->entityManager->clear();

        $unchangedDocument = $this->entityManager->find(Document::class, $missingDocumentId);
        self::assertInstanceOf(Document::class, $unchangedDocument);
        self::assertSame(SourceType::DOC, $unchangedDocument->getFileInfo()->getSourceType());
    }

    public function testRecalculatesDocumentInDossierWithoutProductionReport(): void
    {
        $dossier = WooDecisionFactory::createOne();

        $textDocument = DocumentFactory::createOne(['dossiers' => [$dossier]]);
        $textDocument->getFileInfo()
            ->setMimetype('text/plain')
            ->setSourceType(SourceType::DOC);

        $textDocumentId = $textDocument->getId();
        $this->entityManager->flush();

        $inventoryService = Mockery::mock(InventoryService::class);
        self::getContainer()->set(InventoryService::class, $inventoryService);

        $ingestDispatcher = Mockery::mock(IngestDispatcher::class);
        $ingestDispatcher->expects('dispatchIngestMetadataOnlyCommandForEntity')
            ->with(Mockery::on(static function (Document $value) use ($textDocumentId): bool {
                return $value->getId()->equals($textDocumentId);
            }), false);
        self::getContainer()->set(IngestDispatcher::class, $ingestDispatcher);

        $commandTester = $this->executeCommand();

        self::assertSame(Command::SUCCESS, $commandTester->getStatusCode());

        $this->entityManager->clear();

        $recalculatedDocument = $this->entityManager->find(Document::class, $textDocumentId);
        self::assertInstanceOf(Document::class, $recalculatedDocument);
        self::assertSame(SourceType::TXT, $recalculatedDocument->getFileInfo()->getSourceType());
    }

    public function testContinuesWithNextDossierAfterUnreadableProductionReport(): void
    {
        $exceptionMessage = $this->getFaker()->sentence();
        $publicationContext = $this->getFaker()->publicationContext();
        $documentId = $this->getFaker()->documentId();
        /** @var SourceType $sourceType */
        $sourceType = $this->getFaker()->randomElement([SourceType::PDF, SourceType::SPREADSHEET, SourceType::PRESENTATION, SourceType::EMAIL]);

        $failingDossier = WooDecisionFactory::createOne();
        $failingProductionReport = ProductionReportFactory::createOne(['dossier' => $failingDossier]);
        $failingDocument = DocumentFactory::createOne(['dossiers' => [$failingDossier]]);
        $failingDocument->getFileInfo()->setSourceType(SourceType::UNKNOWN);

        $dossier = WooDecisionFactory::createOne();
        $productionReport = ProductionReportFactory::createOne(['dossier' => $dossier]);
        $unknownDocument = DocumentFactory::createOne([
            'documentId' => $documentId,
            'documentNumber' => DocumentNumber::fromPublicationContextAndDocumentId($publicationContext, $documentId),
            'dossiers' => [$dossier],
        ]);
        $unknownDocument->getFileInfo()->setSourceType(SourceType::UNKNOWN);

        $failingDossierId = $failingDossier->getId();
        $failingDocumentId = $failingDocument->getId();
        $failingProductionReportId = $failingProductionReport->getId();
        $unknownDocumentId = $unknownDocument->getId();
        $productionReportId = $productionReport->getId();
        $this->entityManager->flush();
        $this->entityManager->clear();

        $documentMetadata = new DocumentMetadata(
            date: null,
            filename: $this->getFaker()->word(),
            familyId: null,
            sourceType: $sourceType,
            grounds: [],
            id: $documentId,
            judgement: Judgement::PUBLIC,
            period: null,
            threadId: null,
            inquiryNumbers: InquiryNumbers::empty(),
            suspended: false,
            links: [],
            remark: null,
            publicationContext: $publicationContext,
            refersTo: [],
        );

        $inventoryReader = Mockery::mock(InventoryReaderInterface::class);
        $inventoryReader->expects('getDocumentMetadataGenerator')
            ->andReturn((static function () use ($documentMetadata): Generator {
                yield new InventoryReadItem($documentMetadata, 1, null);
            })());

        $inventoryService = Mockery::mock(InventoryService::class);
        $inventoryService->expects('getReader')
            ->with(Mockery::on(static function (ProductionReport $value) use ($failingProductionReportId): bool {
                return $value->getId()->equals($failingProductionReportId);
            }))
            ->andThrow(new RuntimeException($exceptionMessage));
        $inventoryService->expects('getReader')
            ->with(Mockery::on(static function (ProductionReport $value) use ($productionReportId): bool {
                return $value->getId()->equals($productionReportId);
            }))
            ->andReturn($inventoryReader);
        self::getContainer()->set(InventoryService::class, $inventoryService);

        $ingestDispatcher = Mockery::mock(IngestDispatcher::class);
        $ingestDispatcher->expects('dispatchIngestMetadataOnlyCommandForEntity')
            ->with(Mockery::on(static function (Document $value) use ($unknownDocumentId): bool {
                return $value->getId()->equals($unknownDocumentId);
            }), false);
        self::getContainer()->set(IngestDispatcher::class, $ingestDispatcher);

        $commandTester = $this->executeCommand();

        self::assertSame(Command::FAILURE, $commandTester->getStatusCode());

        $this->entityManager->clear();

        $unchangedDocument = $this->entityManager->find(Document::class, $failingDocumentId);
        self::assertInstanceOf(Document::class, $unchangedDocument);
        self::assertSame(SourceType::UNKNOWN, $unchangedDocument->getFileInfo()->getSourceType());

        $restoredDocument = $this->entityManager->find(Document::class, $unknownDocumentId);
        self::assertInstanceOf(Document::class, $restoredDocument);
        self::assertSame($sourceType, $restoredDocument->getFileInfo()->getSourceType());

        self::assertStringContainsString(
            sprintf('Could not read the production report of dossier %s: %s', $failingDossierId->toRfc4122(), $exceptionMessage),
            $commandTester->getDisplay(),
        );
        self::assertStringContainsString(
            'Restored 1 document source types from production reports.',
            $commandTester->getDisplay(),
        );
    }

    public function testRecalculatesAttachmentsMainDocumentsAndRecordsWithoutElasticsearchDocument(): void
    {
        $attachment = DispositionAttachmentFactory::createOne();
        $attachment->getFileInfo()
            ->setMimetype('text/plain')
            ->setSourceType(SourceType::DOC);

        $mainDocument = DispositionMainDocumentFactory::createOne();
        $mainDocument->getFileInfo()
            ->setMimetype('text/plain')
            ->setSourceType(SourceType::DOC);

        $inquiry = InquiryFactory::createOne([
            'documents' => [],
            'dossiers' => [],
        ]);
        $inquiryInventory = $inquiry->getInventory();
        self::assertInstanceOf(InquiryInventory::class, $inquiryInventory);
        $inquiryInventory->getFileInfo()
            ->setMimetype('text/plain')
            ->setSourceType(SourceType::DOC);

        $attachmentId = $attachment->getId();
        $mainDocumentId = $mainDocument->getId();
        $inquiryInventoryId = $inquiryInventory->getId();
        $this->entityManager->flush();
        $this->entityManager->clear();

        $ingestDispatcher = Mockery::mock(IngestDispatcher::class);
        $ingestDispatcher->expects('dispatchIngestMetadataOnlyCommandForEntity')
            ->with(Mockery::on(static function (EntityWithFileInfo $value) use ($attachmentId): bool {
                if (! $value instanceof DispositionAttachment) {
                    return false;
                }

                return $value->getId()->equals($attachmentId);
            }), false);
        $ingestDispatcher->expects('dispatchIngestMetadataOnlyCommandForEntity')
            ->with(Mockery::on(static function (EntityWithFileInfo $value) use ($mainDocumentId): bool {
                if (! $value instanceof DispositionMainDocument) {
                    return false;
                }

                return $value->getId()->equals($mainDocumentId);
            }), false);
        self::getContainer()->set(IngestDispatcher::class, $ingestDispatcher);

        $commandTester = $this->executeCommand();

        self::assertSame(Command::SUCCESS, $commandTester->getStatusCode());

        $this->entityManager->clear();

        $recalculatedAttachment = $this->entityManager->find(DispositionAttachment::class, $attachmentId);
        self::assertInstanceOf(DispositionAttachment::class, $recalculatedAttachment);
        self::assertSame(SourceType::TXT, $recalculatedAttachment->getFileInfo()->getSourceType());

        $recalculatedMainDocument = $this->entityManager->find(DispositionMainDocument::class, $mainDocumentId);
        self::assertInstanceOf(DispositionMainDocument::class, $recalculatedMainDocument);
        self::assertSame(SourceType::TXT, $recalculatedMainDocument->getFileInfo()->getSourceType());

        $recalculatedInquiryInventory = $this->entityManager->find(InquiryInventory::class, $inquiryInventoryId);
        self::assertInstanceOf(InquiryInventory::class, $recalculatedInquiryInventory);
        self::assertSame(SourceType::TXT, $recalculatedInquiryInventory->getFileInfo()->getSourceType());

        self::assertStringContainsString(
            'updated 3 source types and dispatched 2 Elasticsearch reindex messages; 1 changed records have no Elasticsearch document.',
            $commandTester->getDisplay(),
        );
    }

    private function executeCommand(bool $dryRun = false): CommandTester
    {
        $kernel = self::$kernel;
        Assert::isInstanceOf($kernel, KernelInterface::class);

        $application = new Application($kernel);
        $application->setAutoExit(false);
        self::assertTrue($application->has(RecalculateFileSourceTypes::COMMAND_NAME));

        $commandTester = new CommandTester($application->find(RecalculateFileSourceTypes::COMMAND_NAME));
        $commandTester->execute(
            $dryRun ? ['--dry-run' => true] : [],
            ['interactive' => false],
        );

        return $commandTester;
    }
}
