<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Inventory;

use App\Entity\Document;
use App\Entity\Dossier;
use App\Entity\FileInfo;
use App\Entity\Inquiry;
use App\Entity\Inventory;
use App\Entity\RawInventory;
use App\Message\IngestMetadataOnlyMessage;
use App\Message\RemoveDocumentMessage;
use App\Repository\InquiryRepository;
use App\Service\Inventory\InventoryService;
use App\Service\Inventory\Reader\InventoryReaderFactory;
use App\Service\Storage\DocumentStorageService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Translation\TranslatorInterface;

class InventoryServiceTest extends MockeryTestCase
{
    private EntityManagerInterface|MockInterface $entityManager;
    private InventoryService|MockInterface $inventoryService;
    private LoggerInterface|MockInterface $logger;
    private DocumentStorageService|MockInterface $documentStorage;
    private UploadedFile|MockInterface $uploadedFile;
    private Dossier|MockInterface $dossier;
    private EntityRepository|MockInterface $inventoryRepository;
    private Inventory|MockInterface $inventory;
    private EntityRepository|MockInterface $documentRepository;
    private InquiryRepository|MockInterface $inquiryRepository;
    private TranslatorInterface|MockInterface $translator;
    private MessageBusInterface|MockInterface $messageBus;

    public function setUp(): void
    {
        $this->entityManager = \Mockery::mock(EntityManagerInterface::class);

        $this->documentStorage = \Mockery::mock(DocumentStorageService::class);

        $this->messageBus = \Mockery::mock(MessageBusInterface::class);

        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->logger->shouldReceive('info');

        $this->translator = \Mockery::mock(TranslatorInterface::class);
        $this->translator->shouldReceive('trans')->andReturn('test')->zeroOrMoreTimes();

        $this->inventoryService = new InventoryService(
            $this->entityManager,
            $this->documentStorage,
            new InventoryReaderFactory(),
            $this->translator,
            $this->logger,
            $this->messageBus,
        );

        $this->uploadedFile = \Mockery::mock(UploadedFile::class);
        $this->uploadedFile->expects('getClientOriginalExtension')->andReturn('xlsx');
        $this->uploadedFile->shouldReceive('getClientOriginalName')->andReturn('test-inventory');

        $file = \Mockery::mock(FileInfo::class);
        $file->shouldReceive('setSourceType');
        $file->shouldReceive('setType');
        $file->shouldReceive('setName');
        $file->shouldReceive('getName')->zeroOrMoreTimes()->andReturn('test123');

        $rawFile = \Mockery::mock(FileInfo::class);
        $rawFile->shouldReceive('setSourceType');
        $rawFile->shouldReceive('setType');
        $rawFile->shouldReceive('setName');
        $rawFile->shouldReceive('getName')->zeroOrMoreTimes()->andReturn('rawtest123');

        $this->inventory = \Mockery::mock(Inventory::class);
        $this->inventory
            ->shouldReceive('getFileInfo')
            ->andReturns($file);

        $this->dossier = \Mockery::mock(Dossier::class);
        $this->dossier
            ->shouldReceive('getId')
            ->andReturns(Uuid::v6());
        $this->dossier
            ->shouldReceive('getDossierNr')
            ->andReturns('dossier-123');
        $this->dossier
            ->shouldReceive('addDocument')
            ->with(\Mockery::type(Inventory::class));
        $this->dossier
            ->shouldReceive('getDocumentPrefix')
            ->andReturns('FOOBAR');
        $this->dossier
            ->shouldReceive('getInventory')
            ->andReturns($this->inventory);
        $this->dossier
            ->shouldReceive('setInventory');

        $this->inventory->shouldReceive('getDossiers')->andReturn(new ArrayCollection([$this->dossier]));

        $this->inventoryRepository = \Mockery::mock(EntityRepository::class);

        $this->documentRepository = \Mockery::mock(EntityRepository::class);

        $this->inquiryRepository = \Mockery::mock(InquiryRepository::class);

        $this->entityManager->expects('flush')->zeroOrMoreTimes();
        $this->entityManager->expects('beginTransaction');

        $this->entityManager
            ->shouldReceive('getRepository')
            ->with(Inventory::class)
            ->andReturns($this->inventoryRepository);
        $this->entityManager
            ->shouldReceive('getRepository')
            ->with(Document::class)
            ->andReturns($this->documentRepository);
        $this->entityManager
            ->shouldReceive('getRepository')
            ->with(Inquiry::class)
            ->andReturns($this->inquiryRepository);

        parent::setUp();
    }

    public function testProcessInventoryReturnsErrorWhenInventoryFileCannotBeStored(): void
    {
        $this->entityManager->expects('rollback');

        $filename = __DIR__ . '/inventory-missing-document-id.xlsx';
        $this->documentStorage
            ->expects('storeDocument')
            ->with($this->uploadedFile, \Mockery::type(RawInventory::class))
            ->andReturnTrue();

        $this->entityManager->expects('persist')->with(\Mockery::type(RawInventory::class));
        $this->entityManager->expects('persist')->with(\Mockery::type(Inventory::class));
        $this->entityManager->expects('persist')->with(\Mockery::type(Dossier::class))->zeroOrMoreTimes();

        $this->documentStorage
            ->expects('downloadDocument')
            ->with(\Mockery::type(RawInventory::class))
            ->andReturn($filename);

        $this->documentStorage
            ->expects('removeDownload');

        $this->documentStorage
            ->expects('storeDocument')
            ->with(\Mockery::type(\SplFileInfo::class), \Mockery::type(Inventory::class))
            ->andReturnFalse();

        $this->dossier
            ->shouldReceive('getDocuments')
            ->andReturn(new ArrayCollection([]));

        $this->logger->shouldReceive('error');

        $result = $this->inventoryService->processInventory(
            $this->uploadedFile,
            $this->dossier
        );

        $this->assertFalse($result->isSuccessful());
        $this->assertEquals(
            [
                'Could not store the sanitized inventory spreadsheet.',
            ],
            $result->getGenericErrors()
        );
    }

    public function testProcessInventoryReturnsErrorWhenRawInventoryFileCannotBeStored(): void
    {
        $this->entityManager->expects('rollback');

        $filename = __DIR__ . '/inventory-missing-document-id.xlsx';
        $this->documentStorage
            ->expects('storeDocument')
            ->with($this->uploadedFile, \Mockery::type(RawInventory::class))
            ->andReturnFalse();

        $this->entityManager->expects('persist')->with(\Mockery::type(RawInventory::class));

        $this->logger->shouldReceive('error');

        $result = $this->inventoryService->processInventory(
            $this->uploadedFile,
            $this->dossier
        );

        $this->assertFalse($result->isSuccessful());
        $this->assertEquals(
            [
                'Could not store the inventory spreadsheet.',
            ],
            $result->getGenericErrors()
        );
    }

    public function testProcessInventoryReturnsErrorsWhenInventoryFileHasMissingHeaders(): void
    {
        $this->entityManager->expects('rollback');

        $filename = __DIR__ . '/inventory-missing-columns.xlsx';
        $this->documentStorage
            ->expects('downloadDocument')
            ->with(\Mockery::type(RawInventory::class))
            ->andReturn($filename);

        $this->documentStorage
            ->expects('storeDocument')
            ->with($this->uploadedFile, \Mockery::type(RawInventory::class))
            ->andReturnTrue();

        $this->documentStorage
            ->expects('removeDownload')
            ->with($filename);

        $this->logger->shouldReceive('error');

        $this->entityManager->expects('persist')->with(\Mockery::type(RawInventory::class));

        $result = $this->inventoryService->processInventory(
            $this->uploadedFile,
            $this->dossier,
        );

        $this->assertFalse($result->isSuccessful());
        $this->assertEquals(
            [
                'Error while trying to read the spreadsheet: Could not find the correct headers in the spreadsheet. Missing: date, document, sourcetype, id, threadid, matter',
            ],
            $result->getGenericErrors()
        );
    }

    public function testProcessInventoryReturnsErrorsWhenInventoryFileIsMissingADocumentIdForOneRow(): void
    {
        $this->entityManager->expects('rollback');

        $filename = __DIR__ . '/inventory-missing-document-id.xlsx';
        $this->documentStorage
            ->expects('downloadDocument')
            ->with(\Mockery::type(RawInventory::class))
            ->andReturn($filename);

        $this->documentStorage
            ->expects('storeDocument')
            ->with($this->uploadedFile, \Mockery::type(RawInventory::class))
            ->andReturnTrue();

        $this->documentStorage
            ->expects('storeDocument')
            ->with(\Mockery::type(\SplFileInfo::class), \Mockery::type(Inventory::class))
            ->andReturnTrue();

        $this->documentStorage
            ->expects('removeDownload')
            ->with($filename);

        $this->entityManager->expects('persist')->with(\Mockery::type(RawInventory::class));
        $this->entityManager->expects('persist')->with(\Mockery::type(Inventory::class));
        $this->entityManager->expects('persist')->with(\Mockery::type(Dossier::class));

        $this->logger->shouldReceive('error');

        $this->dossier
            ->shouldReceive('getDocuments')
            ->andReturn(new ArrayCollection([]));

        $this->documentRepository
            ->expects('findOneBy')
            ->with(['documentNr' => 'FOOBAR-56789-5034'])
            ->andReturnNull();

        $this->dossier->expects('addDocument')->with(\Mockery::type(Document::class));
        $this->entityManager->expects('persist')->with(\Mockery::on(
            static function ($document) {
                $document->setId(Uuid::v6());

                return true;
            }
        ));

        $dummyInquiry = \Mockery::mock(Inquiry::class);
        $dummyInquiry->expects('addDocument')->with(\Mockery::type(Document::class));
        $dummyInquiry->expects('setUpdatedAt')->andReturnSelf();

        $this->inquiryRepository
            ->expects('findOneBy')
            ->with(['casenr' => '11-111'])
            ->andReturn($dummyInquiry);

        $this->entityManager->expects('persist')->with($dummyInquiry);
        $this->entityManager->expects('persist')->with($this->dossier)->twice();

        $this->messageBus->expects('dispatch')->once()->with(\Mockery::any())->andReturns(new Envelope(new \stdClass()));

        $result = $this->inventoryService->processInventory(
            $this->uploadedFile,
            $this->dossier
        );

        $this->assertFalse($result->isSuccessful());
        $this->assertEquals(
            [
                2 => [
                    'Error reading row: Error while processing row 2 in the spreadsheet: Missing document ID in inventory row #2',
                ],
            ],
            $result->getRowErrors()
        );
    }

    public function testProcessInventoryReturnsNoErrorsWhenInventoryFileIsValid(): void
    {
        $this->entityManager->expects('commit');

        $filename = __DIR__ . '/inventory-valid.xlsx';

        $this->documentStorage
            ->expects('downloadDocument')
            ->with(\Mockery::type(RawInventory::class))
            ->andReturn($filename);

        $this->documentStorage
            ->expects('storeDocument')
            ->with($this->uploadedFile, \Mockery::type(RawInventory::class))
            ->andReturnTrue();

        $this->documentStorage
            ->expects('storeDocument')
            ->with(\Mockery::type(\SplFileInfo::class), \Mockery::type(Inventory::class))
            ->andReturnTrue();

        $this->documentStorage
            ->expects('removeDownload')
            ->with($filename);

        $this->entityManager->expects('persist')->with(\Mockery::type(RawInventory::class));
        $this->entityManager->expects('persist')->with(\Mockery::type(Inventory::class));
        $this->entityManager->expects('persist')->with($this->dossier)->times(4);

        $this->logger->shouldReceive('error');

        $removedDocumentUuid = Uuid::v6();
        $dummyDocToBeRemoved = \Mockery::mock(Document::class);
        $dummyDocToBeRemoved->expects('getDocumentNr')->andReturn(789);
        $dummyDocToBeRemoved->expects('getId')->andReturn($removedDocumentUuid)->zeroOrMoreTimes();

        $file = \Mockery::mock(FileInfo::class);
        $file->shouldReceive('setSourceType');
        $file->shouldReceive('setType');
        $file->shouldReceive('setName');
        $file->shouldReceive('getName')->zeroOrMoreTimes()->andReturn('test456');

        $existingDocumentUuid = Uuid::v6();
        $dummyDocExisting = \Mockery::mock(Document::class);
        $dummyDocExisting->expects('getDocumentNr')->andReturn(5034)->times(3);
        $dummyDocExisting->expects('getDossiers')->andReturn(new ArrayCollection([$this->dossier]))->times(2);
        $dummyDocExisting->expects('setDocumentDate')->andReturnSelf();
        $dummyDocExisting->expects('setFamilyId')->andReturnSelf();
        $dummyDocExisting->expects('setDocumentId')->andReturnSelf();
        $dummyDocExisting->expects('setThreadId')->andReturnSelf();
        $dummyDocExisting->expects('setJudgement')->andReturnSelf();
        $dummyDocExisting->expects('setGrounds')->andReturnSelf();
        $dummyDocExisting->expects('setSubjects')->andReturnSelf();
        $dummyDocExisting->expects('setPeriod')->andReturnSelf();
        $dummyDocExisting->expects('setDocumentNr')->andReturnSelf();
        $dummyDocExisting->expects('setSuspended')->andReturnSelf();
        $dummyDocExisting->expects('getFileInfo')->andReturns($file);
        $dummyDocExisting->expects('setLink')->andReturnSelf();
        $dummyDocExisting->expects('setRemark')->andReturnSelf();
        $dummyDocExisting->expects('getId')->andReturn($existingDocumentUuid)->zeroOrMoreTimes();
        $dummyDocExisting->expects('shouldBeUploaded')->zeroOrMoreTimes()->andReturnTrue();

        $dummyInquiry = \Mockery::mock(Inquiry::class);
        $dummyInquiry->expects('setUpdatedAt')->andReturnSelf()->twice();
        $dummyInquiry->expects('addDocument')->with($dummyDocExisting);
        $dummyInquiry->expects('addDocument')->with(\Mockery::type(Document::class));
        $dummyInquiry->expects('addDossier')->with($this->dossier);

        $this->dossier
            ->shouldReceive('getDocuments')
            ->andReturn(
                new ArrayCollection([
                    $dummyDocToBeRemoved,
                    $dummyDocExisting,
                ])
            );

        $this->documentRepository
            ->expects('findOneBy')
            ->with(['documentNr' => 'FOOBAR-123-5033'])
            ->andReturnNull();

        $this->documentRepository
            ->expects('findOneBy')
            ->with(['documentNr' => 'FOOBAR-123-5034'])
            ->andReturn($dummyDocExisting);

        $this->inquiryRepository
            ->expects('findOneBy')
            ->with(['casenr' => '11-111'])
            ->andReturn($dummyInquiry)
            ->times(2);

        $newDocumentUuid = Uuid::v6();
        $this->dossier->expects('removeDocument')->with($dummyDocToBeRemoved);
        $this->dossier->expects('addDocument')->with($dummyDocExisting);
        $this->dossier->expects('addDocument')->with(\Mockery::on(
            static function (Document $document) use ($newDocumentUuid) {
                $document->setId($newDocumentUuid);

                return true;
            }
        ));

        $this->entityManager->expects('persist')->with(\Mockery::type(Inquiry::class));
        $this->entityManager->expects('persist')->with(\Mockery::type(Document::class))->twice();
        $this->entityManager->expects('persist')->with($dummyInquiry);

        $this->messageBus->expects('dispatch')->once()
            ->with(\Mockery::on(
                static function ($message) use ($removedDocumentUuid) {
                    return $message instanceof RemoveDocumentMessage && $message->getDocumentId() === $removedDocumentUuid;
                }
            ))
            ->andReturns(new Envelope(new \stdClass()));

        $this->messageBus->expects('dispatch')->once()
            ->with(\Mockery::on(
                static function ($message) use ($existingDocumentUuid) {
                    return $message instanceof IngestMetadataOnlyMessage && $message->getUuid() === $existingDocumentUuid;
                }
            ))
            ->andReturns(new Envelope(new \stdClass()));

        $this->messageBus->expects('dispatch')->once()
            ->with(\Mockery::on(
                static function ($message) use ($newDocumentUuid) {
                    return $message instanceof IngestMetadataOnlyMessage && $message->getUuid() === $newDocumentUuid;
                }
            ))
            ->andReturns(new Envelope(new \stdClass()));

        $result = $this->inventoryService->processInventory(
            $this->uploadedFile,
            $this->dossier,
        );

        $this->assertTrue($result->isSuccessful());
        $this->assertEquals([], $result->getGenericErrors());
        $this->assertEquals([], $result->getRowErrors());
    }
}
