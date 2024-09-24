<?php

declare(strict_types=1);

namespace App\Controller\Admin\Dossier\Disposition;

use App\Domain\Publication\Dossier\Type\Disposition\Disposition;
use App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment;
use App\Domain\Publication\Dossier\Type\Disposition\DispositionDocument;
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
        path: '/balie/dossier/disposition/{prefix}/{dossierId}/document/download',
        name: 'app_admin_disposition_document_download',
        methods: ['GET'],
    )]
    #[IsGranted('AuthMatrix.dossier.read', subject: 'dossier')]
    public function downloadDocument(
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])]
        Disposition $dossier,
        #[MapEntity(expr: 'repository.findForDossierByPrefixAndNr(prefix, dossierId)')]
        DispositionDocument $document,
    ): StreamedResponse {
        unset($dossier); // Only used for isGranted check

        return $this->downloadHelper->getResponseForEntityWithFileInfo($document);
    }

    #[Route(
        path: '/balie/dossier/disposition/{prefix}/{dossierId}/attachment/{attachmentId}/download',
        name: 'app_admin_disposition_attachment_download',
        methods: ['GET'],
    )]
    #[IsGranted('AuthMatrix.dossier.read', subject: 'dossier')]
    public function downloadAttachment(
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])]
        Disposition $dossier,
        #[MapEntity(expr: 'repository.findForDossierByPrefixAndNr(prefix, dossierId, attachmentId)')]
        DispositionAttachment $attachment,
    ): StreamedResponse {
        unset($dossier); // Only used for isGranted check

        return $this->downloadHelper->getResponseForEntityWithFileInfo($attachment);
    }
}
