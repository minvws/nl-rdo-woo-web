<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Inventory;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Generator;
use Mockery;
use Mockery\MockInterface;
use RuntimeException;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\BatchDownload\BatchDownloadScope;
use Shared\Domain\Publication\BatchDownload\BatchDownloadService;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use Shared\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReportDispatcher;
use Shared\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReportProcessRun;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionDispatcher;
use Shared\Domain\Publication\SourceType;
use Shared\Domain\Search\SearchDispatcher;
use Shared\Exception\ProcessInventoryException;
use Shared\Exception\ProductionReportUpdaterException;
use Shared\Exception\TranslatableException;
use Shared\Service\Inquiry\InquiryChangeset;
use Shared\Service\Inquiry\InquiryNumbers;
use Shared\Service\Inquiry\InquiryService;
use Shared\Service\Inventory\DocumentComparator;
use Shared\Service\Inventory\DocumentMetadata;
use Shared\Service\Inventory\DocumentUpdater;
use Shared\Service\Inventory\InventoryChangeset;
use Shared\Service\Inventory\InventoryUpdater;
use Shared\Service\Inventory\Progress\RunProgress;
use Shared\Service\Inventory\Reader\InventoryReaderInterface;
use Shared\Service\Inventory\Reader\InventoryReadItem;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DocumentId;
use Shared\ValueObject\PlainDate;
use Shared\ValueObject\PublicationContext;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class InventoryUpdaterTest extends UnitTestCase
{
    private EntityManagerInterface&MockInterface $entityManager;
    private DocumentUpdater&MockInterface $documentUpdater;
    private DocumentComparator&MockInterface $documentComparator;
    private DocumentRepository&MockInterface $documentRepository;
    private InquiryService&MockInterface $inquiryService;
    private MessageBusInterface&MockInterface $messageBus;
    private SearchDispatcher&MockInterface $searchDispatcher;
    private ProductionReportDispatcher&MockInterface $productionReportDispatcher;
    private BatchDownloadService&MockInterface $batchDownloadService;
    private WooDecisionDispatcher&MockInterface $wooDecisionDispatcher;
    private InventoryUpdater $inventoryUpdater;

    protected function setUp(): void
    {
        $this->entityManager = Mockery::mock(EntityManagerInterface::class);
        $this->documentUpdater = Mockery::mock(DocumentUpdater::class);
        $this->documentComparator = Mockery::mock(DocumentComparator::class);
        $this->documentRepository = Mockery::mock(DocumentRepository::class);
        $this->inquiryService = Mockery::mock(InquiryService::class);
        $this->messageBus = Mockery::mock(MessageBusInterface::class);
        $this->searchDispatcher = Mockery::mock(SearchDispatcher::class);
        $this->productionReportDispatcher = Mockery::mock(ProductionReportDispatcher::class);
        $this->batchDownloadService = Mockery::mock(BatchDownloadService::class);
        $this->wooDecisionDispatcher = Mockery::mock(WooDecisionDispatcher::class);

        $this->inventoryUpdater = new InventoryUpdater(
            $this->entityManager,
            $this->documentUpdater,
            $this->documentComparator,
            $this->documentRepository,
            $this->inquiryService,
            $this->messageBus,
            $this->searchDispatcher,
            $this->productionReportDispatcher,
            $this->batchDownloadService,
            $this->wooDecisionDispatcher,
        );

        parent::setUp();
    }

    public function testSendMessagesForChangesetDispatchesInquiryInventoriesAndDocumentActions(): void
    {
        $dossierId = Uuid::v6();
        $inquiryOneId = Uuid::v6();
        $inquiryTwoId = Uuid::v6();
        $dossier = Mockery::mock(WooDecision::class);
        $dossier->expects('getId')->twice()->andReturn($dossierId);

        $inquiryOne = Mockery::mock(Inquiry::class);
        $inquiryOne->expects('getId')->andReturn($inquiryOneId);

        $inquiryTwo = Mockery::mock(Inquiry::class);
        $inquiryTwo->expects('getId')->andReturn($inquiryTwoId);

        $dossier->expects('getInquiries')->andReturn(new ArrayCollection([$inquiryOne, $inquiryTwo]));

        $updatedDocument = Mockery::mock(Document::class);
        $deletedDocument = Mockery::mock(Document::class);

        $changeset = new InventoryChangeset([
            'pfx-matter-1' => InventoryChangeset::UNCHANGED,
            'pfx-matter-2' => InventoryChangeset::UPDATED,
            'pfx-matter-3' => InventoryChangeset::DELETED,
        ]);

        $runProgress = Mockery::mock(RunProgress::class);
        $runProgress->expects('tick')->times(3);

        $this->productionReportDispatcher->expects('dispatchGenerateInventoryCommand')->with($dossierId);
        $this->wooDecisionDispatcher->expects('dispatchGenerateInquiryInventoryCommand')->with($inquiryOneId);
        $this->wooDecisionDispatcher->expects('dispatchGenerateInquiryInventoryCommand')->with($inquiryTwoId);

        $this->batchDownloadService
            ->expects('refresh')
            ->with(Mockery::on(
                static fn (BatchDownloadScope $scope): bool => $scope->wooDecision === $dossier && $scope->inquiry === null,
            ));

        $this->searchDispatcher->expects('dispatchIndexDossierCommand')->with($dossierId);

        $this->documentRepository
            ->expects('findOneByDocumentNumberCaseInsensitive')
            ->with('pfx-matter-2')
            ->andReturn($updatedDocument);

        $this->documentRepository
            ->expects('findOneByDocumentNumberCaseInsensitive')
            ->with('pfx-matter-3')
            ->andReturn($deletedDocument);

        $this->documentUpdater->expects('asyncUpdate')->with($updatedDocument);
        $this->documentUpdater->expects('asyncRemove')->with($deletedDocument, $dossier);

        $this->entityManager->expects('detach')->with($updatedDocument);
        $this->entityManager->expects('detach')->with($deletedDocument);

        $this->inventoryUpdater->sendMessagesForChangeset($changeset, $dossier, $runProgress);
    }

    public function testUpdateWooDecisionInventoriesWithNoInquiries(): void
    {
        $dossierId = Uuid::v6();
        $dossier = Mockery::mock(WooDecision::class);
        $dossier->expects('getId')->andReturn($dossierId);
        $dossier->expects('getInquiries')->andReturn(new ArrayCollection());

        $this->productionReportDispatcher->expects('dispatchGenerateInventoryCommand')->with($dossierId);

        $this->inventoryUpdater->updateWooDecisionInventories($dossier);
    }

    public function testUpdateWooDecisionInventoriesWithInquiries(): void
    {
        $dossierId = Uuid::v6();
        $inquiryOneId = Uuid::v6();
        $inquiryTwoId = Uuid::v6();

        $dossier = Mockery::mock(WooDecision::class);
        $dossier->expects('getId')->andReturn($dossierId);

        $inquiryOne = Mockery::mock(Inquiry::class);
        $inquiryOne->expects('getId')->andReturn($inquiryOneId);

        $inquiryTwo = Mockery::mock(Inquiry::class);
        $inquiryTwo->expects('getId')->andReturn($inquiryTwoId);

        $dossier->expects('getInquiries')->andReturn(new ArrayCollection([$inquiryOne, $inquiryTwo]));

        $this->productionReportDispatcher->expects('dispatchGenerateInventoryCommand')->with($dossierId);
        $this->wooDecisionDispatcher->expects('dispatchGenerateInquiryInventoryCommand')->with($inquiryOneId);
        $this->wooDecisionDispatcher->expects('dispatchGenerateInquiryInventoryCommand')->with($inquiryTwoId);

        $this->inventoryUpdater->updateWooDecisionInventories($dossier);
    }

    public function testApplyChangesetToDatabaseAddsDocumentAndAppliesReferralUpdates(): void
    {
        $dossier = Mockery::mock(WooDecision::class);
        $dossier->expects('getOrganisation')->andReturn(Mockery::mock(Organisation::class));

        $metadata = new DocumentMetadata(
            date: PlainDate::create('2024-01-15'),
            filename: 'doc-1.pdf',
            familyId: 10,
            sourceType: SourceType::PDF,
            grounds: ['5.1.1a'],
            id: DocumentId::create('1'),
            judgement: Judgement::PUBLIC,
            period: null,
            threadId: null,
            inquiryNumbers: new InquiryNumbers(['21-a']),
            suspended: false,
            links: [],
            remark: null,
            publicationContext: PublicationContext::fromString('PFX-MAT'),
            refersTo: ['matter-77'],
        );

        $reader = Mockery::mock(InventoryReaderInterface::class);
        $reader->expects('getDocumentMetadataGenerator')->with($dossier)->andReturn((static function () use ($metadata) {
            yield new InventoryReadItem($metadata, 1, null);
        })());

        $changeset = new InventoryChangeset([
            'pfx-mat-1' => InventoryChangeset::ADDED,
        ]);

        $runProgress = Mockery::mock(RunProgress::class);
        $runProgress->expects('getCurrentCount')->andReturn(0);
        $runProgress->expects('update')->with(1);

        $createdDocument = Mockery::mock(Document::class);
        $this->documentRepository
            ->expects('findOneByDocumentNumberCaseInsensitive')
            ->with('PFX-MAT-1')
            ->andReturn(null);
        $this->documentRepository
            ->expects('findOneByDocumentNumberCaseInsensitive')
            ->with('PFX-MAT-1')
            ->andReturn($createdDocument);

        $this->documentUpdater
            ->expects('databaseUpdate')
            ->with($metadata, $dossier, Mockery::type(Document::class));

        $this->documentUpdater
            ->expects('updateDocumentReferralsByDocumentNumber')
            ->with($dossier, $createdDocument, ['matter-77']);

        $this->inquiryService
            ->expects('applyChangesetAsync')
            ->with(Mockery::type(InquiryChangeset::class));

        $this->entityManager->expects('flush')->atLeast()->once();
        $this->entityManager->allows('detach');

        $run = Mockery::mock(ProductionReportProcessRun::class);

        $this->inventoryUpdater->applyChangesetToDatabase($run, $dossier, $reader, $changeset, $runProgress);
    }

    public function testApplyChangesetToDatabaseWrapsNonTranslatableRowExceptionAndAborts(): void
    {
        $dossier = Mockery::mock(WooDecision::class);
        $dossier->expects('getOrganisation')
            ->andReturn(Mockery::mock(Organisation::class));

        $metadata = new DocumentMetadata(
            date: PlainDate::create('2024-01-15'),
            filename: 'doc-1.pdf',
            familyId: 10,
            sourceType: SourceType::PDF,
            grounds: ['5.1.1a'],
            id: DocumentId::create('1'),
            judgement: Judgement::PUBLIC,
            period: null,
            threadId: null,
            inquiryNumbers: new InquiryNumbers(['21-a']),
            suspended: false,
            links: [],
            remark: null,
            publicationContext: PublicationContext::fromString('PFX-MAT'),
            refersTo: [],
        );

        $reader = Mockery::mock(InventoryReaderInterface::class);
        $reader->expects('getDocumentMetadataGenerator')
            ->with($dossier)
            ->andReturn((static function () use ($metadata): Generator {
                yield new InventoryReadItem($metadata, 1, null);
            })());

        $changeset = new InventoryChangeset([
            'pfx-mat-1' => InventoryChangeset::ADDED,
        ]);

        $runProgress = Mockery::mock(RunProgress::class);
        $runProgress->expects('getCurrentCount')
            ->andReturn(0);
        $runProgress->expects('update')
            ->with(1);

        $this->documentRepository
            ->expects('findOneByDocumentNumberCaseInsensitive')
            ->with('PFX-MAT-1')
            ->andReturn(null);

        $this->documentUpdater
            ->expects('databaseUpdate')
            ->with($metadata, $dossier, Mockery::type(Document::class))
            ->andThrows(new RuntimeException('some runtime exception'));

        $run = Mockery::mock(ProductionReportProcessRun::class);
        $run->expects('addRowException')
            ->with(1, Mockery::type(TranslatableException::class));

        $this->expectException(RuntimeException::class);
        $this->inventoryUpdater->applyChangesetToDatabase($run, $dossier, $reader, $changeset, $runProgress);
    }

    public function testApplyChangesetToDatabasePassesTranslatableRowExceptionThroughAndAborts(): void
    {
        $dossier = Mockery::mock(WooDecision::class);
        $dossier->expects('getOrganisation')
            ->andReturn(Mockery::mock(Organisation::class));

        $metadata = new DocumentMetadata(
            date: PlainDate::create('2024-01-15'),
            filename: 'doc-1.pdf',
            familyId: 10,
            sourceType: SourceType::PDF,
            grounds: ['5.1.1a'],
            id: DocumentId::create('1'),
            judgement: Judgement::PUBLIC,
            period: null,
            threadId: null,
            inquiryNumbers: new InquiryNumbers(['21-a']),
            suspended: false,
            links: [],
            remark: null,
            publicationContext: PublicationContext::fromString('PFX-MAT'),
            refersTo: [],
        );

        $reader = Mockery::mock(InventoryReaderInterface::class);
        $reader->expects('getDocumentMetadataGenerator')
            ->with($dossier)
            ->andReturn((static function () use ($metadata): Generator {
                yield new InventoryReadItem($metadata, 1, null);
            })());

        $changeset = new InventoryChangeset([
            'pfx-mat-1' => InventoryChangeset::ADDED,
        ]);

        $runProgress = Mockery::mock(RunProgress::class);
        $runProgress->expects('getCurrentCount')
            ->andReturn(0);
        $runProgress->expects('update')
            ->with(1);

        $this->documentRepository
            ->expects('findOneByDocumentNumberCaseInsensitive')
            ->with('PFX-MAT-1')
            ->andReturn(null);

        $thrown = ProcessInventoryException::forGenericRowException(new RuntimeException('some runtime exception'));
        $this->documentUpdater
            ->expects('databaseUpdate')
            ->with($metadata, $dossier, Mockery::type(Document::class))
            ->andThrows($thrown);

        $run = Mockery::mock(ProductionReportProcessRun::class);
        $run->expects('addRowException')
            ->with(1, $thrown);

        $this->expectException(ProcessInventoryException::class);
        $this->inventoryUpdater->applyChangesetToDatabase($run, $dossier, $reader, $changeset, $runProgress);
    }

    public function testApplyChangesetToDatabaseSkipsItemsWithoutDocumentMetadata(): void
    {
        $index = $this->getFaker()->numberBetween(1, 100);

        $dossier = Mockery::mock(WooDecision::class);
        $dossier->expects('getOrganisation')->andReturn(Mockery::mock(Organisation::class));

        $reader = Mockery::mock(InventoryReaderInterface::class);
        $reader->expects('getDocumentMetadataGenerator')
            ->with($dossier)
            ->andReturn((static function () use ($index): Generator {
                yield new InventoryReadItem(null, $index, null);
            })());

        $changeset = new InventoryChangeset([]);

        $runProgress = Mockery::mock(RunProgress::class);
        $runProgress->expects('getCurrentCount')->andReturn(0);
        $runProgress->expects('update')->with($index);

        $this->inquiryService->expects('applyChangesetAsync')->with(Mockery::type(InquiryChangeset::class));
        $this->entityManager->expects('flush')->times(3);

        $run = Mockery::mock(ProductionReportProcessRun::class);

        $this->inventoryUpdater->applyChangesetToDatabase($run, $dossier, $reader, $changeset, $runProgress);
    }

    public function testApplyChangesetToDatabaseSkipsUnchangedDocuments(): void
    {
        $dossier = Mockery::mock(WooDecision::class);
        $dossier->expects('getOrganisation')->andReturn(Mockery::mock(Organisation::class));

        $index = $this->getFaker()->numberBetween(1, 100);
        $metadata = $this->createDocumentMetadata([]);

        $reader = Mockery::mock(InventoryReaderInterface::class);
        $reader->expects('getDocumentMetadataGenerator')
            ->with($dossier)
            ->andReturn((static function () use ($metadata, $index): Generator {
                yield new InventoryReadItem($metadata, $index, null);
            })());

        $changeset = new InventoryChangeset(['pfx-mat-1' => InventoryChangeset::UNCHANGED]);

        $runProgress = Mockery::mock(RunProgress::class);
        $runProgress->expects('getCurrentCount')->andReturn(0);
        $runProgress->expects('update')->with($index);

        $this->inquiryService->expects('applyChangesetAsync')->with(Mockery::type(InquiryChangeset::class));
        $this->entityManager->expects('flush')->times(3);

        $run = Mockery::mock(ProductionReportProcessRun::class);

        $this->inventoryUpdater->applyChangesetToDatabase($run, $dossier, $reader, $changeset, $runProgress);
    }

    public function testApplyChangesetToDatabaseThrowsStateMismatchWhenAddedDocumentAlreadyExists(): void
    {
        $dossier = Mockery::mock(WooDecision::class);
        $dossier->expects('getOrganisation')->andReturn(Mockery::mock(Organisation::class));

        $index = $this->getFaker()->numberBetween(1, 100);
        $metadata = $this->createDocumentMetadata([]);

        $reader = Mockery::mock(InventoryReaderInterface::class);
        $reader->expects('getDocumentMetadataGenerator')
            ->with($dossier)
            ->andReturn((static function () use ($metadata, $index): Generator {
                yield new InventoryReadItem($metadata, $index, null);
            })());

        $changeset = new InventoryChangeset(['pfx-mat-1' => InventoryChangeset::ADDED]);

        $runProgress = Mockery::mock(RunProgress::class);
        $runProgress->expects('getCurrentCount')->andReturn(0);
        $runProgress->expects('update')->with($index);

        $this->documentRepository
            ->expects('findOneByDocumentNumberCaseInsensitive')
            ->with('PFX-MAT-1')
            ->andReturn(Mockery::mock(Document::class));

        $run = Mockery::mock(ProductionReportProcessRun::class);
        $run->expects('addRowException')->with($index, Mockery::type(TranslatableException::class));

        $this->expectException(ProductionReportUpdaterException::class);
        $this->inventoryUpdater->applyChangesetToDatabase($run, $dossier, $reader, $changeset, $runProgress);
    }

    public function testApplyChangesetToDatabaseThrowsStateMismatchWhenDeletedDocumentIsMissing(): void
    {
        $dossier = Mockery::mock(WooDecision::class);
        $dossier->expects('getOrganisation')->andReturn(Mockery::mock(Organisation::class));

        $reader = Mockery::mock(InventoryReaderInterface::class);
        $reader->expects('getDocumentMetadataGenerator')
            ->with($dossier)
            ->andReturn((static function (): Generator {
                yield from [];
            })());

        $documentNumber = $this->getFaker()->word();
        $changeset = new InventoryChangeset([$documentNumber => InventoryChangeset::DELETED]);

        $runProgress = Mockery::mock(RunProgress::class);
        $runProgress->expects('getCurrentCount')->andReturn(0);

        $this->inquiryService->expects('applyChangesetAsync')->with(Mockery::type(InquiryChangeset::class));
        $this->entityManager->expects('flush')->twice();

        $this->documentRepository
            ->expects('findOneByDocumentNumberCaseInsensitive')
            ->with($documentNumber)
            ->andReturnNull();

        $run = Mockery::mock(ProductionReportProcessRun::class);

        $this->expectException(ProductionReportUpdaterException::class);
        $this->inventoryUpdater->applyChangesetToDatabase($run, $dossier, $reader, $changeset, $runProgress);
    }

    public function testApplyChangesetToDatabaseThrowsWhenReferralDocumentIsMissing(): void
    {
        $dossier = Mockery::mock(WooDecision::class);
        $dossier->expects('getOrganisation')->andReturn(Mockery::mock(Organisation::class));

        $index = $this->getFaker()->numberBetween(1, 100);
        $metadata = $this->createDocumentMetadata([$this->getFaker()->word()]);

        $reader = Mockery::mock(InventoryReaderInterface::class);
        $reader->expects('getDocumentMetadataGenerator')
            ->with($dossier)
            ->andReturn((static function () use ($metadata, $index): Generator {
                yield new InventoryReadItem($metadata, $index, null);
            })());

        $changeset = new InventoryChangeset(['pfx-mat-1' => InventoryChangeset::ADDED]);

        $runProgress = Mockery::mock(RunProgress::class);
        $runProgress->expects('getCurrentCount')->andReturn(0);
        $runProgress->expects('update')->with($index);

        $this->documentRepository
            ->expects('findOneByDocumentNumberCaseInsensitive')
            ->with('PFX-MAT-1')
            ->twice()
            ->andReturnNull();

        $this->documentUpdater
            ->expects('databaseUpdate')
            ->with($metadata, $dossier, Mockery::type(Document::class));

        $this->entityManager->expects('flush');
        $this->entityManager->allows('detach');

        $run = Mockery::mock(ProductionReportProcessRun::class);

        $this->expectException(RuntimeException::class);
        $this->inventoryUpdater->applyChangesetToDatabase($run, $dossier, $reader, $changeset, $runProgress);
    }

    public function testSendMessagesForChangesetThrowsStateMismatchWhenDocumentIsMissing(): void
    {
        $dossierId = Uuid::v6();
        $dossier = Mockery::mock(WooDecision::class);
        $dossier->expects('getId')->twice()->andReturn($dossierId);
        $dossier->expects('getInquiries')->andReturn(new ArrayCollection());

        $documentNumber = $this->getFaker()->word();
        $changeset = new InventoryChangeset([$documentNumber => InventoryChangeset::UPDATED]);

        $runProgress = Mockery::mock(RunProgress::class);
        $runProgress->expects('tick');

        $this->productionReportDispatcher->expects('dispatchGenerateInventoryCommand')->with($dossierId);
        $this->batchDownloadService->expects('refresh')->with(Mockery::type(BatchDownloadScope::class));
        $this->searchDispatcher->expects('dispatchIndexDossierCommand')->with($dossierId);

        $this->documentRepository
            ->expects('findOneByDocumentNumberCaseInsensitive')
            ->with($documentNumber)
            ->andReturnNull();

        $this->expectException(ProductionReportUpdaterException::class);
        $this->inventoryUpdater->sendMessagesForChangeset($changeset, $dossier, $runProgress);
    }

    /**
     * @param array<int, string> $refersTo
     */
    private function createDocumentMetadata(array $refersTo): DocumentMetadata
    {
        return new DocumentMetadata(
            date: $this->getFaker()->plainDate(),
            filename: $this->getFaker()->word() . '.pdf',
            familyId: $this->getFaker()->numberBetween(1, 100),
            sourceType: SourceType::PDF,
            grounds: [$this->getFaker()->word()],
            id: DocumentId::create('1'),
            judgement: Judgement::PUBLIC,
            period: null,
            threadId: null,
            inquiryNumbers: new InquiryNumbers([$this->getFaker()->bothify('##-?')]),
            suspended: false,
            links: [],
            remark: null,
            publicationContext: PublicationContext::fromString('PFX-MAT'),
            refersTo: $refersTo,
        );
    }
}
