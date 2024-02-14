<?php

declare(strict_types=1);

namespace App\Controller\Admin\Dossier;

use App\Entity\Document;
use App\Entity\Dossier;
use App\Service\DownloadResponseHelper;
use App\Service\Security\Authorization\AuthorizationMatrix;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DownloadController extends AbstractController
{
    use DossierAuthorizationTrait;

    public function __construct(
        private readonly DownloadResponseHelper $downloadHelper,
        private readonly AuthorizationMatrix $authorizationMatrix,
    ) {
    }

    #[Route('/balie/dossier/{prefix}/{dossierId}/decision/download', name: 'app_admin_dossier_decision_download', methods: ['GET'])]
    #[IsGranted('AuthMatrix.dossier.read')]
    public function downloadDecision(
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])] Dossier $dossier
    ): StreamedResponse {
        $this->testIfDossierIsAllowedByUser($dossier);

        return $this->downloadHelper->getResponseForEntityWithFileInfo($dossier->getDecisionDocument());
    }

    #[Route('/balie/dossier/{prefix}/{dossierId}/inventory/download', name: 'app_admin_dossier_inventory_download', methods: ['GET'])]
    #[IsGranted('AuthMatrix.dossier.read')]
    public function downloadInventory(
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])] Dossier $dossier
    ): StreamedResponse {
        $this->testIfDossierIsAllowedByUser($dossier);

        return $this->downloadHelper->getResponseForEntityWithFileInfo($dossier->getRawInventory());
    }

    #[Route('/balie/dossier/{prefix}/{dossierId}/raw-inventory/download', name: 'app_admin_dossier_raw_inventory_download', methods: ['GET'])]
    #[IsGranted('AuthMatrix.dossier.read')]
    public function downloadRawInventory(
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])] Dossier $dossier
    ): StreamedResponse {
        return $this->downloadHelper->getResponseForEntityWithFileInfo($dossier->getRawInventory());
    }

    #[Route('/balie/dossier/{prefix}/{dossierId}/document/{documentId}', name: 'app_admin_dossier_document_download', methods: ['GET'])]
    #[IsGranted('AuthMatrix.dossier.read')]
    public function downloadDocument(
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])] Dossier $dossier,
        #[MapEntity(mapping: ['documentId' => 'documentNr'])] Document $document,
    ): StreamedResponse {
        $this->testIfDossierIsAllowedByUser($dossier);

        if (! $dossier->getDocuments()->contains($document)) {
            throw new NotFoundHttpException('Document not found in dossier');
        }

        return $this->downloadHelper->getResponseForEntityWithFileInfo($document);
    }
}
