<?php

declare(strict_types=1);

namespace Shared\Service;

use Shared\Domain\Publication\BatchDownload\BatchDownload;
use Shared\Domain\Publication\BatchDownload\BatchDownloadStorage;
use Shared\Domain\Publication\EntityWithFileInfo;
use Shared\Service\Storage\EntityStorageService;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use function fpassthru;
use function sprintf;

readonly class DownloadResponseHelper
{
    public function __construct(
        private EntityStorageService $entityStorageService,
        private BatchDownloadStorage $batchDownloadStorage,
        private DownloadFilenameGenerator $filenameGenerator,
    ) {
    }

    /**
     * @param resource|false|null $stream
     */
    public function getReponseForEntityAndStream(?EntityWithFileInfo $entity, $stream): StreamedResponse
    {
        if (! $entity || ! $entity->getFileInfo()->isUploaded()) {
            throw new NotFoundHttpException('File is not available for download');
        }

        if (! $stream) {
            throw new NotFoundHttpException('File is not available for download');
        }

        $filename = $this->filenameGenerator->getFileName($entity);
        $contentDisposition = $entity->getFileInfo()->getType() === 'pdf' ? 'inline' : 'attachment';

        $response = new StreamedResponse();
        $response->headers->set('Content-Type', $entity->getFileInfo()->getMimetype());
        $response->headers->set('Content-Length', (string) $entity->getFileInfo()->getSize());
        $response->headers->set('Last-Modified', $entity->getUpdatedAt()->format('D, d M Y H:i:s') . ' GMT');
        $response->headers->set('Content-Disposition', sprintf('%s; filename="%s"', $contentDisposition, $filename));

        $response->setCallback(function () use ($stream) {
            fpassthru($stream);
        });

        return $response;
    }

    public function getResponseForEntityWithFileInfo(?EntityWithFileInfo $entity): StreamedResponse
    {
        if (! $entity || ! $entity->getFileInfo()->isUploaded()) {
            throw new NotFoundHttpException('File is not available for download');
        }

        $stream = $this->entityStorageService->retrieveResourceEntity($entity);

        return $this->getReponseForEntityAndStream($entity, $stream);
    }

    public function getResponseForBatchDownload(BatchDownload $batch): StreamedResponse
    {
        $stream = $this->batchDownloadStorage->getFileStreamForBatch($batch);
        if (! $stream) {
            throw new NotFoundHttpException();
        }

        $response = new StreamedResponse();
        $response->headers->set('Content-Type', 'application/zip');
        $response->headers->set('Content-Length', (string) $batch->getSize());
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $batch->getFilename() . '"');
        $response->headers->set('Cache-Control', 'no-store');
        $response->setCallback(function () use ($stream) {
            fpassthru($stream);
        });

        return $response;
    }
}
