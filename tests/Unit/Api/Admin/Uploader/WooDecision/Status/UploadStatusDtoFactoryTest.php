<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Api\Admin\Uploader\WooDecision\Status;

use Doctrine\Common\Collections\ArrayCollection;
use Mockery\MockInterface;
use Shared\Api\Admin\Uploader\WooDecision\Status\UploadedFileDto;
use Shared\Api\Admin\Uploader\WooDecision\Status\UploadStatusDtoFactory;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\DocumentFileService;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileSet;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileUpdate;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileUpload;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Enum\DocumentFileSetStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Enum\DocumentFileUpdateType;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DossierUploadStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\FileInfo;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

final class UploadStatusDtoFactoryTest extends UnitTestCase
{
    private DocumentFileService&MockInterface $documentFileService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->documentFileService = \Mockery::mock(DocumentFileService::class);
    }

    public function testMake(): void
    {
        $doc = \Mockery::mock(Document::class);
        $uploadStatus = \Mockery::mock(DossierUploadStatus::class);
        $uploadStatus->shouldReceive('getExpectedUploadCount')->andReturn(4);
        $uploadStatus->shouldReceive('getUploadedDocuments')->andReturn(new ArrayCollection([$doc]));
        $uploadStatus->shouldReceive('getMissingDocumentIds')->andReturn(new ArrayCollection(['1002', '1004', '1005']));

        /** @var WooDecision&MockInterface $wooDecision */
        $wooDecision = \Mockery::mock(WooDecision::class);
        $wooDecision->shouldReceive('getId')->andReturn(Uuid::fromString('00000000-0000-0000-0000-000000000001'));
        $wooDecision->shouldReceive('getDocuments')->andReturn($this->getDocuments());
        $wooDecision->expects('getUploadStatus')->andReturn($uploadStatus);

        /** @var DocumentFileSet&MockInterface $documentFileSet */
        $documentFileSet = \Mockery::mock(DocumentFileSet::class);
        $documentFileSet->shouldReceive('getStatus')->andReturn(DocumentFileSetStatus::OPEN_FOR_UPLOADS);
        $documentFileSet->shouldReceive('getUploads')->andReturn($this->getUploads());

        $this->documentFileService->shouldReceive('canProcess')->with($documentFileSet)->andReturn(true);

        $result = (new UploadStatusDtoFactory($this->documentFileService))->make($wooDecision, $documentFileSet);

        $this->assertMatchesYamlSnapshot([
            'dossierId' => $result->wooDecision->getId()->__toString(),
            'status' => $result->status,
            'canProcess' => $result->canProcess,
            'uploadedFiles' => array_map(
                fn (UploadedFileDto $dto) => [
                    'id' => $dto->id,
                    'name' => $dto->name,
                    'mimeType' => $dto->mimeType,
                ],
                $result->uploadedFiles,
            ),
            'expectedDocumentsCount' => $result->expectedDocumentsCount,
            'currentDocumentsCount' => $result->currentDocumentsCount,
            'missingDocuments' => $result->missingDocuments,
            'changes' => $result->changes->getArrayCopy(),
        ]);
    }

    public function testMakeWithChanges(): void
    {
        $doc = \Mockery::mock(Document::class);
        $uploadStatus = \Mockery::mock(DossierUploadStatus::class);
        $uploadStatus->shouldReceive('getExpectedUploadCount')->andReturn(4);
        $uploadStatus->shouldReceive('getUploadedDocuments')->andReturn(new ArrayCollection([$doc]));
        $uploadStatus->shouldReceive('getMissingDocumentIds')->andReturn(new ArrayCollection(['1002', '1004', '1005']));

        /** @var WooDecision&MockInterface $wooDecision */
        $wooDecision = \Mockery::mock(WooDecision::class);
        $wooDecision->shouldReceive('getId')->andReturn(Uuid::fromString('00000000-0000-0000-0000-000000000001'));
        $wooDecision->shouldReceive('getDocuments')->andReturn($this->getDocuments());
        $wooDecision->expects('getUploadStatus')->andReturn($uploadStatus);

        /** @var DocumentFileSet&MockInterface $documentFileSet */
        $documentFileSet = \Mockery::mock(DocumentFileSet::class);
        $documentFileSet->shouldReceive('getStatus')->andReturn(DocumentFileSetStatus::NEEDS_CONFIRMATION);
        $documentFileSet->shouldReceive('getUploads')->andReturn(new ArrayCollection());
        $documentFileSet->shouldReceive('getUpdates')->andReturn($this->getUpdates());

        $this->documentFileService->shouldReceive('canProcess')->with($documentFileSet)->andReturn(true);

        $result = (new UploadStatusDtoFactory($this->documentFileService))->make($wooDecision, $documentFileSet);

        $this->assertMatchesYamlSnapshot([
            'dossierId' => $result->wooDecision->getId()->__toString(),
            'status' => $result->status,
            'canProcess' => $result->canProcess,
            'uploadedFiles' => array_map(
                fn (UploadedFileDto $dto) => [
                    'id' => $dto->id,
                    'name' => $dto->name,
                    'mimeType' => $dto->mimeType,
                ],
                $result->uploadedFiles,
            ),
            'expectedDocumentsCount' => $result->expectedDocumentsCount,
            'currentDocumentsCount' => $result->currentDocumentsCount,
            'missingDocuments' => $result->missingDocuments,
            'changes' => $result->changes->getArrayCopy(),
        ]);
    }

    /**
     * @return ArrayCollection<array-key,Document&MockInterface>
     */
    private function getDocuments(): ArrayCollection
    {
        return new ArrayCollection([
            $this->getDocument(documentId: '1001', name: 'file1.pdf', shouldBeUploaded: false, isUploaded: false),
            $this->getDocument(documentId: '1002', name: 'file2.pdf', shouldBeUploaded: true, isUploaded: false),
            $this->getDocument(documentId: '1003', name: 'archive1.zip', shouldBeUploaded: true, isUploaded: true),
            $this->getDocument(documentId: '1004', name: 'archive2.7zip', shouldBeUploaded: true, isUploaded: false),
            $this->getDocument(documentId: '1005', name: 'file3.pdf', shouldBeUploaded: true, isUploaded: false),
        ]);
    }

    private function getDocument(string $documentId, string $name, bool $shouldBeUploaded, bool $isUploaded): Document&MockInterface
    {
        /** @var Document&MockInterface $document */
        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('getFileInfo')->andReturn($this->getFileInfo($name));
        $document->shouldReceive('getDocumentId')->andReturn($documentId);
        $document->shouldReceive('shouldBeUploaded')->andReturn($shouldBeUploaded);
        $document->shouldReceive('isUploaded')->andReturn($isUploaded);

        return $document;
    }

    /**
     * @return ArrayCollection<array-key,DocumentFileUpload&MockInterface>
     */
    private function getUploads(): ArrayCollection
    {
        return new ArrayCollection([
            $this->getDocumentFileUpload('file1.pdf', 'application/pdf', '00000000-0000-0000-0000-000000000001'),
            $this->getDocumentFileUpload('archive.zip', 'application/zip', '00000000-0000-0000-0000-000000000002'),
            $this->getDocumentFileUpload('file3.pdf', 'application/pdf', '00000000-0000-0000-0000-000000000003'),
        ]);
    }

    private function getDocumentFileUpload(string $name, string $mimeType, string $uuid): DocumentFileUpload&MockInterface
    {
        $upload = \Mockery::mock(DocumentFileUpload::class);
        $upload->shouldReceive('getId')->andReturn(Uuid::fromString($uuid));
        $upload->shouldReceive('getFileInfo')->andReturn($this->getFileInfo($name, $mimeType));

        return $upload;
    }

    private function getFileInfo(string $name, ?string $mimeType = null): FileInfo&MockInterface
    {
        /** @var FileInfo&MockInterface $fileInfo */
        $fileInfo = \Mockery::mock(FileInfo::class);
        $fileInfo->shouldReceive('getName')->andReturn($name);
        $fileInfo->shouldReceive('getMimeType')->andReturn($mimeType);

        return $fileInfo;
    }

    private function getUpdates(): ArrayCollection
    {
        return new ArrayCollection([
            $this->getDocumentFileUpdate(DocumentFileUpdateType::ADD),
            $this->getDocumentFileUpdate(DocumentFileUpdateType::REPUBLISH),
            $this->getDocumentFileUpdate(DocumentFileUpdateType::UPDATE),
            $this->getDocumentFileUpdate(DocumentFileUpdateType::UPDATE),
            $this->getDocumentFileUpdate(DocumentFileUpdateType::ADD),
        ]);
    }

    private function getDocumentFileUpdate(DocumentFileUpdateType $updateType): DocumentFileUpdate&MockInterface
    {
        $update = \Mockery::mock(DocumentFileUpdate::class);
        $update->shouldReceive('getType')->andReturn($updateType);

        return $update;
    }
}
