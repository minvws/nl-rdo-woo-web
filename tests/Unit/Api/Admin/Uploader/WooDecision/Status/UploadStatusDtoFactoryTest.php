<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Api\Admin\Uploader\WooDecision\Status;

use Admin\Api\Admin\Uploader\WooDecision\Status\UploadedFileDto;
use Admin\Api\Admin\Uploader\WooDecision\Status\UploadStatusDtoFactory;
use Doctrine\Common\Collections\ArrayCollection;
use Mockery;
use Mockery\MockInterface;
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

use function array_map;

final class UploadStatusDtoFactoryTest extends UnitTestCase
{
    private DocumentFileService&MockInterface $documentFileService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->documentFileService = Mockery::mock(DocumentFileService::class);
    }

    public function testMake(): void
    {
        $doc = Mockery::mock(Document::class);

        $uploadStatus = Mockery::mock(DossierUploadStatus::class);
        $uploadStatus->expects('getExpectedUploadCount')->andReturn(4);
        $uploadStatus->expects('getUploadedDocuments')->andReturn(new ArrayCollection([$doc]));
        $uploadStatus->expects('getMissingDocumentIds')->andReturn(new ArrayCollection(['1002', '1004', '1005']));

        $wooDecision = Mockery::mock(WooDecision::class);
        $wooDecision->expects('getId')->times(2)->andReturn(Uuid::fromString('00000000-0000-0000-0000-000000000001'));
        $wooDecision->expects('getUploadStatus')->andReturn($uploadStatus);

        $documentFileSet = Mockery::mock(DocumentFileSet::class);
        $documentFileSet->expects('getStatus')->times(2)->andReturn(DocumentFileSetStatus::OPEN_FOR_UPLOADS);
        $documentFileSet->expects('getUploads')->andReturn($this->getUploads());

        $this->documentFileService->expects('canProcess')->with($documentFileSet)->andReturn(true);

        $result = new UploadStatusDtoFactory($this->documentFileService)->make($wooDecision, $documentFileSet);

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
        $doc = Mockery::mock(Document::class);
        $uploadStatus = Mockery::mock(DossierUploadStatus::class);
        $uploadStatus->expects('getExpectedUploadCount')->andReturn(4);
        $uploadStatus->expects('getUploadedDocuments')->andReturn(new ArrayCollection([$doc]));
        $uploadStatus->expects('getMissingDocumentIds')->andReturn(new ArrayCollection(['1002', '1004', '1005']));

        $wooDecision = Mockery::mock(WooDecision::class);
        $wooDecision->expects('getId')->times(2)->andReturn(Uuid::fromString('00000000-0000-0000-0000-000000000001'));
        $wooDecision->expects('getUploadStatus')->andReturn($uploadStatus);

        $documentFileSet = Mockery::mock(DocumentFileSet::class);
        $documentFileSet->expects('getStatus')->times(2)->andReturn(DocumentFileSetStatus::NEEDS_CONFIRMATION);
        $documentFileSet->expects('getUploads')->andReturn(new ArrayCollection());
        $documentFileSet->expects('getUpdates')->andReturn($this->getUpdates());

        $this->documentFileService->expects('canProcess')->with($documentFileSet)->andReturn(true);

        $result = new UploadStatusDtoFactory($this->documentFileService)->make($wooDecision, $documentFileSet);

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
        $upload = Mockery::mock(DocumentFileUpload::class);
        $upload->expects('getId')->andReturn(Uuid::fromString($uuid));
        $upload->expects('getFileInfo')->times(2)->andReturn($this->getFileInfo($name, $mimeType));

        return $upload;
    }

    private function getFileInfo(string $name, ?string $mimeType = null): FileInfo&MockInterface
    {
        $fileInfo = Mockery::mock(FileInfo::class);
        $fileInfo->expects('getName')->andReturn($name);
        $fileInfo->expects('getMimeType')->andReturn($mimeType);

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
        $update = Mockery::mock(DocumentFileUpdate::class);
        $update->expects('getType')->andReturn($updateType);

        return $update;
    }
}
