<?php

declare(strict_types=1);

namespace App\Controller\Public\Dossier;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\FileProvider\DossierFileProviderManager;
use App\Domain\Publication\Dossier\FileProvider\DossierFileType;
use App\Service\DownloadResponseHelper;
use App\Service\Storage\ThumbnailStorageService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class DossierFileController extends AbstractController
{
    public function __construct(
        private readonly ThumbnailStorageService $thumbnailStorage,
        private readonly DownloadResponseHelper $downloadHelper,
        private readonly DossierFileProviderManager $fileProviderManager,
    ) {
    }

    #[Cache(maxage: 3600, public: true, mustRevalidate: true)]
    #[Route('/dossier/{prefix}/{dossierId}/file/download/{type}/{id?""}', name: 'app_dossier_file_download', methods: ['GET'])]
    public function download(
        #[ValueResolver('dossierWithAccessCheck')] AbstractDossier $dossier,
        DossierFileType $type,
        string $id,
    ): StreamedResponse {
        $entity = $this->fileProviderManager->getEntityForPublicUse($type, $dossier, $id);

        return $this->downloadHelper->getResponseForEntityWithFileInfo($entity);
    }

    #[Cache(maxage: 31536000, smaxage: 31536000, public: true)]
    #[Route(
        '/dossier/{prefix}/{dossierId}/file/thumbnail/{type}/{id}/{pageNr}/{hash}',
        name: 'app_dossier_file_thumbnail',
        requirements: ['pageNr' => '\d+'],
        defaults: ['hash' => ''],
        methods: ['GET']
    )]
    public function thumbnail(
        #[ValueResolver('dossierWithAccessCheck')] AbstractDossier $dossier,
        DossierFileType $type,
        string $id,
        string $pageNr,
    ): StreamedResponse {
        $entity = $this->fileProviderManager->getEntityForPublicUse($type, $dossier, $id);

        $fileSize = $this->thumbnailStorage->fileSize($entity, intval($pageNr));
        $stream = $this->thumbnailStorage->retrieveResource($entity, intval($pageNr));
        if ($stream === null) {
            // Display default placeholder thumbnail if we haven't found a thumbnail for given document/pageNr
            $path = sprintf('%s/%s', $this->getParameter('kernel.project_dir') . '/public', 'placeholder.png');
            $fileSize = filesize($path);
            $stream = fopen($path, 'rb');
            if ($stream === false) {
                throw new NotFoundHttpException();
            }
        }

        $response = new StreamedResponse();
        $response->headers->set('Content-Type', 'image/png');
        $response->headers->set('Content-Length', (string) $fileSize);
        $response->setCallback(function () use ($stream) {
            fpassthru($stream);
        });

        return $response;
    }
}
