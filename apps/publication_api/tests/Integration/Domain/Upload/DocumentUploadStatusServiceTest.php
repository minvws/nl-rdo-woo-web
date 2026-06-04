<?php

declare(strict_types=1);

namespace Integration\Domain\Upload;

use PublicationApi\Domain\Upload\DocumentUploadStatusService;
use PublicationApi\Domain\Upload\UploadStatus;
use PublicationApi\Tests\Integration\Api\ApiPublicationV1TestCase;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentWithdrawReason;
use Shared\Domain\Upload\Exception\UploadValidationException;
use Shared\Tests\Factory\DocumentFactory;
use Shared\Tests\Factory\FileInfoFactory;
use Shared\Tests\Factory\UploadEntityFactory;
use Symfony\Component\HttpFoundation\InputBag;

class DocumentUploadStatusServiceTest extends ApiPublicationV1TestCase
{
    public function testGetUploadStatusWhenFileUploaded(): void
    {
        $document = DocumentFactory::createOne([
            'fileInfo' => FileInfoFactory::new([
                'uploaded' => true,
            ]),
        ]);

        $documentUploadStatusService = self::fromContainer(DocumentUploadStatusService::class);
        $uploadStatus = $documentUploadStatusService->getUploadStatus($document);

        self::assertEquals(UploadStatus::PROCESSED, $uploadStatus);
    }

    public function testGetUploadStatusWhenDocumentWithdrawn(): void
    {
        $document = DocumentFactory::createOne([
            'fileInfo' => FileInfoFactory::new([
                'uploaded' => true,
            ]),
        ]);
        $document->withdraw(DocumentWithdrawReason::SUSPENDED_DOCUMENT, 'explanation');

        $documentUploadStatusService = self::fromContainer(DocumentUploadStatusService::class);
        $uploadStatus = $documentUploadStatusService->getUploadStatus($document);

        self::assertEquals(UploadStatus::NO_UPLOAD_REQUIRED, $uploadStatus);
    }

    public function testGetUploadStatusWhenDocumentSuspended(): void
    {
        $document = DocumentFactory::createOne([
            'suspended' => true,
            'fileInfo' => FileInfoFactory::new([
                'uploaded' => true,
            ]),
        ]);

        $documentUploadStatusService = self::fromContainer(DocumentUploadStatusService::class);
        $uploadStatus = $documentUploadStatusService->getUploadStatus($document);

        self::assertEquals(UploadStatus::NO_UPLOAD_REQUIRED, $uploadStatus);
    }

    public function testGetUploadStatusWhenFileNotUploaded(): void
    {
        $document = DocumentFactory::createOne([
            'fileInfo' => FileInfoFactory::new([
                'uploaded' => false,
            ]),
        ]);

        $documentUploadStatusService = self::fromContainer(DocumentUploadStatusService::class);
        $uploadStatus = $documentUploadStatusService->getUploadStatus($document);

        self::assertEquals(UploadStatus::UPLOAD_REQUIRED, $uploadStatus);
    }

    public function testGetUploadStatusWhenFileInvalid(): void
    {
        $document = DocumentFactory::createOne([
            'fileInfo' => FileInfoFactory::new([
                'uploaded' => false,
            ]),
        ]);
        $uploadEntity = UploadEntityFactory::createOne([
            'user' => null,
            'context' => new InputBag([
                'documentId' => $document->getId()->toRfc4122(),
            ]),
        ]);
        $uploadEntity->finishUploading('filename', 1);
        $uploadEntity->failValidation(new UploadValidationException());

        $documentUploadStatusService = self::fromContainer(DocumentUploadStatusService::class);
        $uploadStatus = $documentUploadStatusService->getUploadStatus($document);

        self::assertEquals(UploadStatus::PROCESSING_FAILED, $uploadStatus);
    }

    public function testGetUploadStatusWhenFileUploadedButNotProcessed(): void
    {
        $document = DocumentFactory::createOne([
            'fileInfo' => FileInfoFactory::new([
                'uploaded' => false,
            ]),
        ]);
        $uploadEntity = UploadEntityFactory::createOne([
            'user' => null,
            'context' => new InputBag([
                'documentId' => $document->getId()->toRfc4122(),
            ]),
        ]);
        $uploadEntity->finishUploading('filename', 1);

        $documentUploadStatusService = self::fromContainer(DocumentUploadStatusService::class);
        $uploadStatus = $documentUploadStatusService->getUploadStatus($document);

        self::assertEquals(UploadStatus::PROCESSING, $uploadStatus);
    }
}
