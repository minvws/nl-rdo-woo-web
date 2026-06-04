<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration\Domain\Upload;

use PublicationApi\Domain\Upload\MainDocumentUploadStatusService;
use PublicationApi\Domain\Upload\UploadStatus;
use PublicationApi\Tests\Integration\Api\ApiPublicationV1TestCase;
use Shared\Domain\Upload\Exception\UploadValidationException;
use Shared\Tests\Factory\FileInfoFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionMainDocumentFactory;
use Shared\Tests\Factory\UploadEntityFactory;
use Symfony\Component\HttpFoundation\InputBag;

class MainDocumentUploadStatusServiceTest extends ApiPublicationV1TestCase
{
    public function testGetUploadStatusWhenFileUploaded(): void
    {
        $mainDocument = WooDecisionMainDocumentFactory::createOne([
            'fileInfo' => FileInfoFactory::new([
                'uploaded' => true,
            ]),
        ]);

        $mainDocumentUploadStatusService = self::fromContainer(MainDocumentUploadStatusService::class);
        $uploadStatus = $mainDocumentUploadStatusService->getUploadStatus($mainDocument);

        self::assertEquals(UploadStatus::PROCESSED, $uploadStatus);
    }

    public function testGetUploadStatusWhenFileNotUploaded(): void
    {
        $mainDocument = WooDecisionMainDocumentFactory::createOne([
            'fileInfo' => FileInfoFactory::new([
                'uploaded' => false,
            ]),
        ]);

        $mainDocumentUploadStatusService = self::fromContainer(MainDocumentUploadStatusService::class);
        $uploadStatus = $mainDocumentUploadStatusService->getUploadStatus($mainDocument);

        self::assertEquals(UploadStatus::UPLOAD_REQUIRED, $uploadStatus);
    }

    public function testGetUploadStatusWhenFileInvalid(): void
    {
        $mainDocument = WooDecisionMainDocumentFactory::createOne([
            'fileInfo' => FileInfoFactory::new([
                'uploaded' => false,
            ]),
        ]);
        $uploadEntity = UploadEntityFactory::createOne([
            'user' => null,
            'context' => new InputBag([
                'mainDocumentId' => $mainDocument->getId()->toRfc4122(),
            ]),
        ]);
        $uploadEntity->finishUploading('filename', 1);
        $uploadEntity->failValidation(new UploadValidationException());

        $mainDocumentUploadStatusService = self::fromContainer(MainDocumentUploadStatusService::class);
        $uploadStatus = $mainDocumentUploadStatusService->getUploadStatus($mainDocument);

        self::assertEquals(UploadStatus::PROCESSING_FAILED, $uploadStatus);
    }

    public function testGetUploadStatusWhenFileUploadedButNotProcessed(): void
    {
        $mainDocument = WooDecisionMainDocumentFactory::createOne([
            'fileInfo' => FileInfoFactory::new([
                'uploaded' => false,
            ]),
        ]);
        $uploadEntity = UploadEntityFactory::createOne([
            'user' => null,
            'context' => new InputBag([
                'mainDocumentId' => $mainDocument->getId()->toRfc4122(),
            ]),
        ]);
        $uploadEntity->finishUploading('filename', 1);

        $mainDocumentUploadStatusService = self::fromContainer(MainDocumentUploadStatusService::class);
        $uploadStatus = $mainDocumentUploadStatusService->getUploadStatus($mainDocument);

        self::assertEquals(UploadStatus::PROCESSING, $uploadStatus);
    }
}
