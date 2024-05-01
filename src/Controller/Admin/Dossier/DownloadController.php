<?php

declare(strict_types=1);

namespace App\Controller\Admin\Dossier;

use App\Entity\Document;
use App\Entity\Dossier;
use App\Service\DownloadResponseHelper;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DownloadController extends AbstractController
{
    public function __construct(
        private readonly DownloadResponseHelper $downloadHelper,
    ) {
    }

    #[Route(
        path: '/balie/dossier/woodecision/download/decision/{prefix}/{dossierId}',
        name: 'app_admin_dossier_decision_download',
        methods: ['GET'],
    )]
    #[IsGranted('AuthMatrix.dossier.read', subject: 'dossier')]
    public function downloadDecision(
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])] Dossier $dossier
    ): StreamedResponse {
        return $this->downloadHelper->getResponseForEntityWithFileInfo($dossier->getDecisionDocument());
    }

    #[Route(
        path: '/balie/dossier/woodecision/download/inventory/{prefix}/{dossierId}',
        name: 'app_admin_dossier_inventory_download',
        methods: ['GET'],
    )]
    #[IsGranted('AuthMatrix.dossier.read', subject: 'dossier')]
    public function downloadInventory(
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])] Dossier $dossier
    ): StreamedResponse {
        return $this->downloadHelper->getResponseForEntityWithFileInfo($dossier->getInventory());
    }

    #[Route(
        path: '/balie/dossier/woodecision/download/raw-inventory/{prefix}/{dossierId}',
        name: 'app_admin_dossier_raw_inventory_download',
        methods: ['GET'],
    )]
    #[IsGranted('AuthMatrix.dossier.read', subject: 'dossier')]
    public function downloadRawInventory(
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])] Dossier $dossier
    ): StreamedResponse {
        return $this->downloadHelper->getResponseForEntityWithFileInfo($dossier->getRawInventory());
    }

    #[Route(
        path: '/balie/dossier/woodecision/download/document/{prefix}/{dossierId}/{documentId}',
        name: 'app_admin_dossier_document_download',
        methods: ['GET'],
    )]
    #[IsGranted('AuthMatrix.dossier.read', subject: 'dossier')]
    public function downloadDocument(
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])] Dossier $dossier,
        #[MapEntity(mapping: ['documentId' => 'documentNr'])] Document $document,
    ): StreamedResponse {
        if (! $dossier->getDocuments()->contains($document)) {
            throw new NotFoundHttpException('Document not found in dossier');
        }

        return $this->downloadHelper->getResponseForEntityWithFileInfo($document);
    }
}
