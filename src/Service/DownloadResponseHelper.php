<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\BatchDownload;
use App\Entity\EntityWithFileInfo;
use App\Service\Storage\DocumentStorageService;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DownloadResponseHelper
{
    public function __construct(
        private readonly DocumentStorageService $documentStorage,
        private readonly ArchiveService $archiveService,
    ) {
    }

    public function getResponseForEntityWithFileInfo(
        ?EntityWithFileInfo $entity,
        bool $asAttachment = true,
        ?string $filename = null,
    ): StreamedResponse {
        if (! $entity || ! $entity->getFileInfo()->isUploaded()) {
            throw new NotFoundHttpException('File is not available for download');
        }

        $stream = $this->documentStorage->retrieveResourceDocument($entity);
        if (! $stream) {
            throw new NotFoundHttpException('File is not available for download');
        }

        $response = new StreamedResponse();
        $response->headers->set('Content-Type', $entity->getFileInfo()->getMimetype());
        $response->headers->set('Content-Length', (string) $entity->getFileInfo()->getSize());
        $response->headers->set('Last-Modified', $entity->getUpdatedAt()->format('D, d M Y H:i:s') . ' GMT');

        if ($asAttachment) {
            $filename = $filename ?? $entity->getFileInfo()->getName();
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        }

        $response->setCallback(function () use ($stream) {
            fpassthru($stream);
        });

        return $response;
    }

    public function getResponseForBatchDownload(BatchDownload $batch): StreamedResponse
    {
        $stream = $this->archiveService->getZipStream($batch);
        if (! $stream) {
            throw new NotFoundHttpException();
        }

        $response = new StreamedResponse();
        $response->headers->set('Content-Type', 'application/zip');
        $response->headers->set('Content-Length', $batch->getSize());
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $batch->getFilename() . '"');
        $response->setCallback(function () use ($stream) {
            fpassthru($stream);
        });

        return $response;
    }
}
