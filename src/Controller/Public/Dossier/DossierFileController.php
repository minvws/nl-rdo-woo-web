<?php

declare(strict_types=1);

namespace Shared\Controller\Public\Dossier;

use Shared\Controller\Public\Dossier\WooDecision\DocumentController;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\FileProvider\DossierFileProviderManager;
use Shared\Domain\Publication\Dossier\FileProvider\DossierFileType;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Service\DownloadResponseHelper;
use Shared\Service\Storage\ThumbnailStorageService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

use function filesize;
use function fopen;
use function fpassthru;
use function intval;
use function sprintf;

class DossierFileController extends AbstractController
{
    public const string ROUTE_NAME_DOSSIER_FILE_DOWNLOAD = 'app_dossier_file_download';
    public const string ROUTE_NAME_DOSSIER_FILE_THUMBNAIL = 'app_dossier_file_thumbnail';

    public function __construct(
        private readonly ThumbnailStorageService $thumbnailStorage,
        private readonly DownloadResponseHelper $downloadHelper,
        private readonly DossierFileProviderManager $fileProviderManager,
    ) {
    }

    #[Cache(maxage: 600, public: true, mustRevalidate: true)]
    #[Route('/dossier/{documentPrefix}/{dossierNumber}/file/download/{type}/{id?""}', self::ROUTE_NAME_DOSSIER_FILE_DOWNLOAD, methods: ['GET'])]
    public function download(
        #[ValueResolver('dossierWithAccessCheck')] AbstractDossier $dossier,
        DossierFileType $type,
        string $id,
    ): StreamedResponse|RedirectResponse {
        $entity = $this->fileProviderManager->getEntityForPublicUse($type, $dossier, $id);

        if ($entity instanceof Document) {
            return $this->redirectToRoute(
                DocumentController::ROUTE_NAME_DOCUMENT_DOWNLOAD,
                ['documentNumber' => $entity->getDocumentNumber()->toString()],
                Response::HTTP_MOVED_PERMANENTLY,
            );
        }

        return $this->downloadHelper->getResponseForEntityWithFileInfo($entity);
    }

    #[Cache(maxage: 600, smaxage: 600, public: true)]
    #[Route(
        '/dossier/{documentPrefix}/{dossierNumber}/file/thumbnail/{type}/{id}/{pageNr}/{hash}',
        self::ROUTE_NAME_DOSSIER_FILE_THUMBNAIL,
        requirements: ['pageNr' => '\d+'],
        defaults: ['hash' => ''],
        methods: ['GET'],
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
        $response->setCallback(static function () use ($stream) {
            fpassthru($stream);
        });

        return $response;
    }
}
