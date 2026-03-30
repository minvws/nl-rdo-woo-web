<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication;

use ApiPlatform\Validator\Exception\ValidationException;
use Shared\Domain\Publication\Attachment\Command\UpdateAttachmentCommand;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Upload\UploadEntity;
use Shared\Domain\Upload\UploadEntityRepository;
use Shared\Domain\Upload\UploadRequest;
use Shared\Domain\Upload\UploadService;
use Shared\Service\Uploader\UploadGroupId;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\ConstraintViolationList;

use function file_put_contents;
use function sprintf;
use function sys_get_temp_dir;
use function unlink;

use const UPLOAD_ERR_OK;

class AttachmentUploadProcessor
{
    use HandleTrait;

    public function __construct(
        private Filesystem $filesystem,
        private UploadEntityRepository $uploadEntityRepository,
        private UploadService $uploadService,
        MessageBusInterface $messageBus,
    ) {
        $this->messageBus = $messageBus;
    }

    public function process(
        Uuid $dossierId,
        UploadGroupId $uploadGroupId,
        string $content,
        string $fileName,
        AbstractAttachment $attachment,
    ): void {
        $tempPath = $this->filesystem->tempnam(sys_get_temp_dir(), 'api_upload_', sprintf('_%s', $fileName));

        $uploadedBytes = file_put_contents($tempPath, $content);
        if ($uploadedBytes === 0 || $uploadedBytes === false) {
            throw new ValidationException(ConstraintViolationList::createFromMessage('Could not write file content'));
        }

        try {
            $uploadedFile = new UploadedFile($tempPath, $fileName, null, UPLOAD_ERR_OK, true);

            $uploadEntityId = Uuid::v6()->toRfc4122();

            $context = new InputBag();
            $context->set('attachmentId', $attachment->getId()->toRfc4122());
            $context->set('dossierId', $dossierId->toRfc4122());

            $uploadEntity = new UploadEntity($uploadEntityId, $uploadGroupId, null, $context);
            $this->uploadEntityRepository->save($uploadEntity, true);

            $additionalParameters = new InputBag();
            $additionalParameters->set('dossierId', $dossierId->toRfc4122());

            $chunkIndex = 1;
            $chunkCount = 1;
            $uploadRequest = new UploadRequest($chunkIndex, $chunkCount, $uploadEntityId, $uploadedFile, $uploadGroupId, $additionalParameters);
            $this->uploadService->handleUploadRequest($uploadRequest, null);

            $this->handle(new UpdateAttachmentCommand(
                dossierId: $dossierId,
                attachmentId: $attachment->getId(),
                formalDate: null,
                internalReference: null,
                type: null,
                language: null,
                grounds: null,
                uploadFileReference: $uploadEntityId,
            ));
        } finally {
            unlink($tempPath);
        }
    }
}
