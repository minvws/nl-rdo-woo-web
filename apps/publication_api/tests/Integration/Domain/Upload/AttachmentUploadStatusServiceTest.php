<?php

declare(strict_types=1);

namespace Integration\Domain\Upload;

use PublicationApi\Domain\Upload\AttachmentUploadStatusService;
use PublicationApi\Domain\Upload\UploadStatus;
use PublicationApi\Tests\Integration\Api\ApiPublicationV1TestCase;
use Shared\Domain\Upload\Exception\UploadValidationException;
use Shared\Tests\Factory\FileInfoFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionAttachmentFactory;
use Shared\Tests\Factory\UploadEntityFactory;
use Symfony\Component\HttpFoundation\InputBag;

class AttachmentUploadStatusServiceTest extends ApiPublicationV1TestCase
{
    public function testGetUploadStatusWhenFileUploaded(): void
    {
        $wooDecisionAttachment = WooDecisionAttachmentFactory::createOne([
            'fileInfo' => FileInfoFactory::new([
                'uploaded' => true,
            ]),
        ]);

        $attachmentUploadStatusService = self::fromContainer(AttachmentUploadStatusService::class);
        $uploadStatus = $attachmentUploadStatusService->getUploadStatus($wooDecisionAttachment);

        self::assertEquals(UploadStatus::PROCESSED, $uploadStatus);
    }

    public function testGetUploadStatusWhenFileNotUploaded(): void
    {
        $wooDecisionAttachment = WooDecisionAttachmentFactory::createOne([
            'fileInfo' => FileInfoFactory::new([
                'uploaded' => false,
            ]),
        ]);

        $attachmentUploadStatusService = self::fromContainer(AttachmentUploadStatusService::class);
        $uploadStatus = $attachmentUploadStatusService->getUploadStatus($wooDecisionAttachment);

        self::assertEquals(UploadStatus::UPLOAD_REQUIRED, $uploadStatus);
    }

    public function testGetUploadStatusWhenFileInvalid(): void
    {
        $wooDecisionAttachment = WooDecisionAttachmentFactory::createOne([
            'fileInfo' => FileInfoFactory::new([
                'uploaded' => false,
            ]),
        ]);
        $uploadEntity = UploadEntityFactory::createOne([
            'user' => null,
            'context' => new InputBag([
                'attachmentId' => $wooDecisionAttachment->getId()->toRfc4122(),
            ]),
        ]);
        $uploadEntity->finishUploading('filename', 1);
        $uploadEntity->failValidation(new UploadValidationException());

        $attachmentUploadStatusService = self::fromContainer(AttachmentUploadStatusService::class);
        $uploadStatus = $attachmentUploadStatusService->getUploadStatus($wooDecisionAttachment);

        self::assertEquals(UploadStatus::PROCESSING_FAILED, $uploadStatus);
    }

    public function testGetUploadStatusWhenFileUploadedButNotProcessed(): void
    {
        $wooDecisionAttachment = WooDecisionAttachmentFactory::createOne([
            'fileInfo' => FileInfoFactory::new([
                'uploaded' => false,
            ]),
        ]);
        $uploadEntity = UploadEntityFactory::createOne([
            'user' => null,
            'context' => new InputBag([
                'attachmentId' => $wooDecisionAttachment->getId()->toRfc4122(),
            ]),
        ]);
        $uploadEntity->finishUploading('filename', 1);

        $attachmentUploadStatusService = self::fromContainer(AttachmentUploadStatusService::class);
        $uploadStatus = $attachmentUploadStatusService->getUploadStatus($wooDecisionAttachment);

        self::assertEquals(UploadStatus::PROCESSING, $uploadStatus);
    }
}
