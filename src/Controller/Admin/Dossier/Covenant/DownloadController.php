<?php

declare(strict_types=1);

namespace App\Controller\Admin\Dossier\Covenant;

use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument;
use App\Service\DownloadResponseHelper;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DownloadController extends AbstractController
{
    public function __construct(
        private readonly DownloadResponseHelper $downloadHelper,
    ) {
    }

    #[Route(
        path: '/balie/dossier/covenant/{prefix}/{dossierId}/covenant-document/download',
        name: 'app_admin_covenant_covenantdocument_download',
        methods: ['GET'],
    )]
    #[IsGranted('AuthMatrix.dossier.read', subject: 'covenant')]
    public function downloadCovenantDocument(
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])]
        Covenant $covenant,
        #[MapEntity(expr: 'repository.findForDossierPrefixAndNr(prefix, dossierId)')]
        CovenantDocument $covenantDocument,
    ): StreamedResponse {
        unset($covenant); // Only used for isGranted check

        return $this->downloadHelper->getResponseForEntityWithFileInfo($covenantDocument);
    }

    #[Route(
        path: '/balie/dossier/covenant/{prefix}/{dossierId}/covenant-attachment/{attachmentId}/download',
        name: 'app_admin_covenant_covenantattachment_download',
        methods: ['GET'],
    )]
    #[IsGranted('AuthMatrix.dossier.read', subject: 'covenant')]
    public function downloadCovenantAttachment(
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])]
        Covenant $covenant,
        #[MapEntity(expr: 'repository.findForDossierPrefixAndNr(prefix, dossierId, attachmentId)')]
        CovenantAttachment $covenantAttachment,
    ): StreamedResponse {
        unset($covenant); // Only used for isGranted check

        return $this->downloadHelper->getResponseForEntityWithFileInfo($covenantAttachment);
    }
}
