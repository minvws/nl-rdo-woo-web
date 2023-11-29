<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\BatchDownload;
use App\Entity\Document;
use App\Entity\Dossier;
use App\Message\GenerateArchiveMessage;
use App\Repository\BatchDownloadRepository;
use App\Service\ArchiveService;
use App\Service\BatchDownloadService;
use App\ValueObject\TranslatableMessage;
use Doctrine\Common\Collections\ArrayCollection;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Translation\TranslatorInterface;

class BatchDownloadServiceTest extends MockeryTestCase
{
    private BatchDownloadRepository|MockInterface $batchRepository;
    private MessageBusInterface|MockInterface $messageBus;
    private ArchiveService|MockInterface $archiveService;
    private BatchDownloadService $service;
    private TranslatorInterface|MockInterface $translator;

    public function setUp(): void
    {
        $this->batchRepository = \Mockery::mock(BatchDownloadRepository::class);
        $this->messageBus = \Mockery::mock(MessageBusInterface::class);
        $this->archiveService = \Mockery::mock(ArchiveService::class);
        $this->translator = \Mockery::mock(TranslatorInterface::class);

        $this->service = new BatchDownloadService(
            $this->batchRepository,
            $this->messageBus,
            $this->archiveService,
            $this->translator,
        );

        parent::setUp();
    }

    public function testRefreshForDossierRemovesAllBatchesAndCreatesANewBatchWithAllDocuments(): void
    {
        $docA = \Mockery::mock(Document::class);
        $docA->expects('getDocumentNr')->andReturns('doc-a');
        $docA->expects('shouldBeUploaded')->andReturnTrue();
        $docA->expects('isUploaded')->andReturnTrue();

        $docB = \Mockery::mock(Document::class);
        $docB->expects('getDocumentNr')->andReturns('doc-b');
        $docB->expects('shouldBeUploaded')->andReturnTrue();
        $docB->expects('isUploaded')->andReturnTrue();

        $dossierUuid = Uuid::v6();

        $dossier = \Mockery::mock(Dossier::class);
        $dossier->expects('isAvailableForBatchDownload')->andReturnTrue();
        $dossier->expects('getDocuments')->andReturn(new ArrayCollection([$docA, $docB]));
        $dossier->expects('getId')->andReturn($dossierUuid);
        $dossier->expects('getDownloadFilePrefix')->andReturn(new TranslatableMessage('x', ['y' => 'z']));
        $this->translator->expects('trans')->with('x', ['y' => 'z'])->andReturns('foo-bar');

        $batchA = \Mockery::mock(BatchDownload::class);
        $batchB = \Mockery::mock(BatchDownload::class);

        $this->batchRepository->expects('findBy')->with(['dossier' => $dossier])->andReturns([$batchA, $batchB]);

        $this->archiveService->expects('removeZip')->with($batchA);
        $this->archiveService->expects('removeZip')->with($batchB);

        $this->batchRepository->expects('remove')->with($batchA);
        $this->batchRepository->expects('remove')->with($batchB);
        $this->batchRepository->expects('pruneExpired');
        $this->batchRepository->expects('findBy')->with(['dossier' => $dossier])->andReturns([]); // To simulate deletion

        $batchUuid = Uuid::v6();

        $this->batchRepository->expects('save')->with(\Mockery::on(
            function (BatchDownload $batch) use ($batchUuid, $dossier): bool {
                $batch->setId($batchUuid);
                $this->assertEquals(BatchDownload::STATUS_PENDING, $batch->getStatus());
                $this->assertEquals($dossier, $batch->getEntity());
                $this->assertEquals(0, $batch->getDownloaded());
                $this->assertEquals(['doc-a', 'doc-b'], $batch->getDocuments());

                return true;
            }
        ));

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (GenerateArchiveMessage $message) use ($batchUuid) {
                return $message->getUuid() === $batchUuid;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->service->refreshForEntity($dossier);
    }

    public function testRefreshForDossierDoesNotGenerateNewArchiveForEntityThatIsNotAvailableForBatchDownload(): void
    {
        $dossier = \Mockery::mock(Dossier::class);
        $dossier->expects('isAvailableForBatchDownload')->andReturnFalse();

        $batchA = \Mockery::mock(BatchDownload::class);
        $batchB = \Mockery::mock(BatchDownload::class);

        $this->batchRepository->expects('findBy')->with(['dossier' => $dossier])->andReturns([$batchA, $batchB]);

        $this->archiveService->expects('removeZip')->with($batchA);
        $this->archiveService->expects('removeZip')->with($batchB);

        $this->batchRepository->expects('remove')->with($batchA);
        $this->batchRepository->expects('remove')->with($batchB);

        $this->service->refreshForEntity($dossier);
    }

    public function testRemove(): void
    {
        $batch = \Mockery::mock(BatchDownload::class);

        $this->archiveService->expects('removeZip')->with($batch);
        $this->batchRepository->expects('remove')->with($batch);

        $this->service->remove($batch);
    }

    public function testFindOrCreateReusesExistingBatch(): void
    {
        $docNrs = ['doc-x', 'doc-y'];

        $dossier = \Mockery::mock(Dossier::class);

        $expectedBatch = \Mockery::mock(BatchDownload::class);
        $expectedBatch->expects('getStatus')->andReturns(BatchDownload::STATUS_COMPLETED);
        $expectedBatch->expects('getDocuments')->andReturns($docNrs);

        $this->batchRepository->expects('pruneExpired');
        $this->batchRepository->expects('findBy')->with(['dossier' => $dossier])->andReturns([$expectedBatch]);

        $batch = $this->service->findOrCreate($dossier, $docNrs, false);

        $this->assertSame($expectedBatch, $batch);
    }
}
