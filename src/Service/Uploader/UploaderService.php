<?php

declare(strict_types=1);

namespace App\Service\Uploader;

use App\Entity\EntityWithFileInfo;
use App\Exception\UploaderServiceException;
use App\Service\Storage\DocumentStorageService;
use App\SourceType;
use Oneup\UploaderBundle\Event\PostUploadEvent;
use Oneup\UploaderBundle\Uploader\Storage\FilesystemOrphanageStorage;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\RequestStack;
use Webmozart\Assert\Assert;

readonly class UploaderService
{
    public function __construct(
        private RequestStack $requestStack,
        private FilesystemOrphanageStorage $orphanageStorage,
        private DocumentStorageService $documentStorage,
    ) {
    }

    public function registerUpload(PostUploadEvent $event, UploadGroupId $uploaderGroupId = UploadGroupId::DEFAULT): void
    {
        $session = $this->requestStack->getSession();

        $uploads = $session->get($this->getSessionKey($uploaderGroupId), []);
        Assert::isArray($uploads);

        $uploads[$event->getRequest()->get('uuid')] = [$event->getFile()->getPathname()];

        $session->set($this->getSessionKey($uploaderGroupId), $uploads);
    }

    /**
     * @return array<File>
     */
    public function confirmUpload(string $uploadUuid, UploadGroupId $uploadGroupId = UploadGroupId::DEFAULT): array
    {
        $session = $this->requestStack->getSession();

        $uploads = $session->get($this->getSessionKey($uploadGroupId), []);
        Assert::isArray($uploads);

        $uploadedPaths = $uploads[$uploadUuid] ?? null;
        if (is_null($uploadedPaths)) {
            return [];
        }
        Assert::isArray($uploadedPaths);
        Assert::allString($uploadedPaths);

        $finder = $this->getFinder($uploadGroupId, $uploadedPaths);

        if (! $finder->hasResults()) {
            return [];
        }

        $session->remove($this->getSessionKey($uploadGroupId));

        return $this->orphanageStorage->uploadFiles(iterator_to_array($finder));
    }

    public function confirmSingleUpload(string $uploadUuid, UploadGroupId $uploadGroupId = UploadGroupId::DEFAULT): File
    {
        $files = $this->confirmUpload($uploadUuid, $uploadGroupId);
        $numberOfFiles = count($files);

        if ($numberOfFiles === 0) {
            throw UploaderServiceException::forNoFilesUploaded($uploadUuid);
        }

        if ($numberOfFiles > 1) {
            throw UploaderServiceException::forMultipleFilesUploaded($uploadUuid);
        }

        return current($files);
    }

    public function attachFileToEntity(string $uuid, EntityWithFileInfo $entity, UploadGroupId $uploadGroupId): void
    {
        $file = $this->confirmSingleUpload($uuid, $uploadGroupId);

        $fileInfo = $entity->getFileInfo();

        if ($fileInfo->isUploaded()) {
            $this->documentStorage->removeFileForEntity($entity);
            $fileInfo->removeFileProperties();
        }

        $fileInfo->setSourceType(SourceType::SOURCE_PDF);
        $fileInfo->setType('pdf');

        if (! $this->documentStorage->storeDocument($file, $entity)) {
            throw UploaderServiceException::forCouldNotAttachFileToEntity($entity);
        }

        unlink($file->getPathname());
    }

    private function getSessionKey(UploadGroupId $uploadGroupId): string
    {
        return sprintf('uploads_%s', $uploadGroupId->value);
    }

    /**
     * @param array<array-key,string> $paths
     */
    private function getFinder(UploadGroupId $uploadGroupId, array $paths): Finder
    {
        return $this->orphanageStorage
            ->getFiles()
            ->path(
                array_map(fn (string $path): string => sprintf('%s/%s', $uploadGroupId->value, basename($path)), $paths),
            );
    }
}
