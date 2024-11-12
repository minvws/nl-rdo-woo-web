<?php

declare(strict_types=1);

namespace App\Controller\Admin\Dossier;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\FileProvider\DossierFileProviderManager;
use App\Domain\Publication\Dossier\FileProvider\DossierFileType;
use App\Service\DownloadResponseHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DossierFileController extends AbstractController
{
    public function __construct(
        private readonly DownloadResponseHelper $downloadHelper,
        private readonly DossierFileProviderManager $fileProviderManager,
    ) {
    }

    #[Cache(maxage: 3600, public: true, mustRevalidate: true)]
    #[Route(
        '/balie/dossier/{prefix}/{dossierId}/file/download/{type}/{id?""}',
        name: 'app_admin_dossier_file_download',
        methods: ['GET'],
    )]
    #[IsGranted('AuthMatrix.dossier.read', subject: 'dossier')]
    public function download(
        #[ValueResolver('dossierWithAccessCheck')] AbstractDossier $dossier,
        DossierFileType $type,
        string $id,
    ): StreamedResponse {
        $entity = $this->fileProviderManager->getEntityForAdminUse($type, $dossier, $id);

        return $this->downloadHelper->getResponseForEntityWithFileInfo($entity, $type);
    }
}
