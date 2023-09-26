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

class DownloadController extends AbstractController
{
    public function __construct(
        private readonly DownloadResponseHelper $downloadHelper,
    ) {
    }

    #[Route('/balie/dossier/{dossierId}/decision/download', name: 'app_admin_dossier_decision_download', methods: ['GET'])]
    public function downloadDecision(
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier
    ): StreamedResponse {
        return $this->downloadHelper->getResponseForEntityWithFileInfo($dossier->getDecisionDocument());
    }

    #[Route('/balie/dossier/{dossierId}/inventory/download', name: 'app_admin_dossier_inventory_download', methods: ['GET'])]
    public function downloadInventory(
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier
    ): StreamedResponse {
        return $this->downloadHelper->getResponseForEntityWithFileInfo($dossier->getInventory());
    }

    #[Route('/balie/dossier/{dossierId}/raw-inventory/download', name: 'app_admin_dossier_raw_inventory_download', methods: ['GET'])]
    public function downloadRawInventory(
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier
    ): StreamedResponse {
        return $this->downloadHelper->getResponseForEntityWithFileInfo($dossier->getRawInventory());
    }

    #[Route('/balie/dossier/{dossierId}/document/{documentId}', name: 'app_admin_dossier_document_download', methods: ['GET'])]
    public function downloadDocument(
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier,
        #[MapEntity(mapping: ['documentId' => 'documentNr'])] Document $document,
    ): StreamedResponse {
        if (! $dossier->getDocuments()->contains($document)) {
            throw new NotFoundHttpException('Document not found in dossier');
        }

        return $this->downloadHelper->getResponseForEntityWithFileInfo($document);
    }
}
