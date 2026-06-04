<?php

declare(strict_types=1);

namespace PublicationApi\Api\Uploads\MainDocument;

use ApiPlatform\Validator\Exception\ValidationException;
use Psr\Http\Message\StreamInterface;
use PublicationApi\Domain\Upload\MainDocumentUploadStatusService;
use PublicationApi\Domain\Upload\UploadStatus;
use PublicationApi\Domain\Upload\UploadValidationService;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;
use Shared\Domain\Publication\MainDocument\Command\UpdateMainDocumentCommand;
use Shared\Domain\Upload\StreamUpload;
use Shared\Domain\Upload\UploadService;
use Shared\Service\Storage\FileHashService;
use Shared\Service\Uploader\UploadGroupId;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\ConstraintViolationList;
use Webmozart\Assert\Assert;

class MainDocumentUploadProcessor
{
    use HandleTrait;

    public function __construct(
        private readonly MainDocumentUploadStatusService $mainDocumentUploadStatusService,
        private readonly UploadValidationService $uploadValidationService,
        private readonly UploadService $uploadService,
        MessageBusInterface $messageBus,
    ) {
        $this->messageBus = $messageBus;
    }

    public function process(
        AbstractDossier $dossier,
        AbstractMainDocument $mainDocument,
        StreamInterface $content,
    ): void {
        if ($this->isAlreadyUploaded($mainDocument, $content)) {
            return;
        }

        $uploadId = Uuid::v6();
        $fileName = $mainDocument->getFileInfo()->getName();
        Assert::string($fileName);

        $streamUpload = new StreamUpload(
            fileName: $fileName,
            stream: $content,
            groupId: UploadGroupId::MAIN_DOCUMENTS,
            additionalParameters: [
                'dossierId' => $dossier->getId()->toRfc4122(),
                'mainDocumentId' => $mainDocument->getId()->toRfc4122(),
            ],
            uploadId: $uploadId->toRfc4122(),
        );

        $this->uploadService->handleUpload($streamUpload);

        $violations = $this->uploadValidationService->getValidationErrorsForUpload($uploadId);
        if ($violations !== []) {
            throw new ValidationException(new ConstraintViolationList($violations));
        }

        $this->handle(new UpdateMainDocumentCommand(
            dossierId: $dossier->getId(),
            uploadFileReference: $uploadId->toRfc4122(),
        ));
    }

    private function isAlreadyUploaded(AbstractMainDocument $mainDocument, StreamInterface $stream): bool
    {
        $mainDocumentHash = $mainDocument->getFileInfo()->getHash();
        if ($mainDocumentHash === null) {
            return false;
        }

        if ($this->mainDocumentUploadStatusService->getUploadStatus($mainDocument) !== UploadStatus::PROCESSED) {
            return false;
        }

        return $mainDocumentHash === FileHashService::calculatePsrStreamHash($stream);
    }
}
