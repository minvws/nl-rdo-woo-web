<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication;

use ApiPlatform\Validator\Exception\ValidationException;
use Shared\Domain\Upload\UploadEntity;
use Shared\Domain\Upload\UploadEntityRepository;
use Shared\Domain\Upload\UploadRequest;
use Shared\Domain\Upload\UploadService;
use Shared\Service\Uploader\UploadGroupId;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\ConstraintViolationList;

use function file_put_contents;
use function sprintf;
use function sys_get_temp_dir;
use function unlink;

use const UPLOAD_ERR_OK;

readonly class UploadProcessor
{
    public function __construct(
        private Filesystem $filesystem,
        private UploadEntityRepository $uploadEntityRepository,
        private UploadService $uploadService,
    ) {
    }

    public function process(
        Uuid $dossierId,
        UploadGroupId $uploadGroupId,
        string $content,
        string $fileName,
    ): void {
        $tempPath = $this->filesystem->tempnam(sys_get_temp_dir(), 'api_upload_', sprintf('_%s', $fileName));

        $uploadedBytes = file_put_contents($tempPath, $content);
        if ($uploadedBytes === 0 || $uploadedBytes === false) {
            throw new ValidationException(ConstraintViolationList::createFromMessage('Could not write file content'));
        }

        try {
            $uploadedFile = new UploadedFile($tempPath, $fileName, null, UPLOAD_ERR_OK, true);

            $uploadEntityId = Uuid::v6()->toRfc4122();

            $additionalParameters = new InputBag();
            $additionalParameters->set('dossierId', $dossierId->toRfc4122());

            $uploadEntity = new UploadEntity($uploadEntityId, $uploadGroupId, null, $additionalParameters);
            $this->uploadEntityRepository->save($uploadEntity, true);

            $chunkIndex = 1;
            $chunkCount = 1;
            $uploadRequest = new UploadRequest($chunkIndex, $chunkCount, $uploadEntityId, $uploadedFile, $uploadGroupId, $additionalParameters);
            $this->uploadService->handleUploadRequest($uploadRequest, null);
        } finally {
            unlink($tempPath);
        }
    }
}
