<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication;

use Psr\Http\Message\StreamInterface;
use PublicationApi\Domain\Upload\AttachmentUploadStatusService;
use Shared\Domain\Publication\Attachment\Command\UpdateAttachmentCommand;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Upload\StreamUpload;
use Shared\Domain\Upload\UploadService;
use Shared\Service\Storage\FileHashService;
use Shared\Service\Uploader\UploadGroupId;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

class AttachmentUploadProcessor
{
    use HandleTrait;

    public function __construct(
        private readonly AttachmentUploadStatusService $attachmentUploadStatusService,
        private readonly UploadService $uploadService,
        MessageBusInterface $messageBus,
    ) {
        $this->messageBus = $messageBus;
    }

    public function process(
        AbstractDossier $dossier,
        AbstractAttachment $attachment,
        StreamInterface $content,
    ): void {
        if ($this->isAlreadyUploaded($attachment, $content)) {
            return;
        }

        $uploadId = Uuid::v6();
        $fileName = $attachment->getFileInfo()->getName();
        Assert::string($fileName);

        $streamUpload = new StreamUpload(
            fileName: $fileName,
            stream: $content,
            groupId: UploadGroupId::ATTACHMENTS,
            additionalParameters: [
                'dossierId' => $dossier->getId()->toRfc4122(),
                'attachmentId' => $attachment->getId()->toRfc4122(),
            ],
            uploadId: $uploadId->toRfc4122(),
        );

        $this->uploadService->handleUpload($streamUpload);

        $this->handle(new UpdateAttachmentCommand(
            dossierId: $dossier->getId(),
            attachmentId: $attachment->getId(),
            uploadFileReference: $uploadId->toRfc4122(),
        ));
    }

    private function isAlreadyUploaded(AbstractAttachment $attachment, StreamInterface $stream): bool
    {
        $attachmentHash = $attachment->getFileInfo()->getHash();
        if ($attachmentHash === null) {
            return false;
        }

        if ($this->attachmentUploadStatusService->getUploadStatus($attachment) !== UploadStatus::PROCESSED) {
            return false;
        }

        return $attachmentHash === FileHashService::calculatePsrStreamHash($stream);
    }
}
