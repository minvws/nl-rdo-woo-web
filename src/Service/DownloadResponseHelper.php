<?php

declare(strict_types=1);

namespace App\Service;

use App\Domain\Publication\Dossier\FileProvider\DossierFileType;
use App\Entity\BatchDownload;
use App\Entity\Document;
use App\Entity\EntityWithFileInfo;
use App\Service\Storage\EntityStorageService;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Mime\MimeTypes;
use Webmozart\Assert\Assert;

class DownloadResponseHelper
{
    public function __construct(
        private readonly EntityStorageService $entityStorageService,
        private readonly ArchiveService $archiveService,
    ) {
    }

    public function getResponseForEntityWithFileInfo(?EntityWithFileInfo $entity, DossierFileType $type): StreamedResponse
    {
        if (! $entity || ! $entity->getFileInfo()->isUploaded()) {
            throw new NotFoundHttpException('File is not available for download');
        }

        $stream = $this->entityStorageService->retrieveResourceEntity($entity);
        if (! $stream) {
            throw new NotFoundHttpException('File is not available for download');
        }

        $response = new StreamedResponse();
        $response->headers->set('Content-Type', $entity->getFileInfo()->getMimetype());
        $response->headers->set('Content-Length', (string) $entity->getFileInfo()->getSize());
        $response->headers->set('Last-Modified', $entity->getUpdatedAt()->format('D, d M Y H:i:s') . ' GMT');
        $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s"', $this->getFileName($entity, $type)));

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

    private function getFileName(EntityWithFileInfo $entity, DossierFileType $type): string
    {
        if ($type === DossierFileType::DOCUMENT) {
            /** @var Document $entity */
            Assert::isInstanceOf($entity, Document::class);

            $mimeType = $entity->getFileInfo()->getMimetype();
            Assert::string($mimeType);

            $ext = MimeTypes::getDefault()->getExtensions($mimeType)[0] ?? null;
            Assert::string($ext);

            return sprintf('%s.%s', $entity->getDocumentNr(), $ext);
        }

        $filename = $entity->getFileInfo()->getName();
        Assert::string($filename);

        return $filename;
    }
}
