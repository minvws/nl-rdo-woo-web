<?php

declare(strict_types=1);

namespace Shared\Controller\Admin\Dossier;

use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\FileProvider\DossierFileProviderManager;
use Shared\Domain\Publication\Dossier\FileProvider\DossierFileType;
use Shared\Service\DownloadResponseHelper;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DossierFileController extends AbstractController
{
    public const string ROUTE_NAME_DOSSIER_FILE_DOWNLOAD = 'app_admin_dossier_file_download';

    public function __construct(
        private readonly DownloadResponseHelper $downloadHelper,
        private readonly DossierFileProviderManager $fileProviderManager,
    ) {
    }

    #[Cache(maxage: 600, public: true, mustRevalidate: true)]
    #[Route(
        '/balie/dossier/{documentPrefix}/{dossierNumber}/file/download/{type}/{id?""}',
        self::ROUTE_NAME_DOSSIER_FILE_DOWNLOAD,
        methods: ['GET'],
    )]
    #[IsGranted('AuthMatrix.dossier.read', subject: 'dossier')]
    public function download(
        #[MapEntity(mapping: ['documentPrefix' => 'documentPrefix', 'dossierNumber' => 'dossierNumber'])] AbstractDossier $dossier,
        DossierFileType $type,
        string $id,
    ): StreamedResponse {
        $entity = $this->fileProviderManager->getEntityForAdminUse($type, $dossier, $id);

        return $this->downloadHelper->getResponseForEntityWithFileInfo($entity);
    }
}
