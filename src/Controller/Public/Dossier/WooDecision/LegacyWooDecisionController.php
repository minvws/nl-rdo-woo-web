<?php

declare(strict_types=1);

namespace App\Controller\Public\Dossier\WooDecision;

use App\Entity\BatchDownload;
use App\Entity\Dossier;
use App\Service\DossierService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Routing\Annotation\Route;

// Redirects old dossier urls (without prefixes in the url) to the new urls
class LegacyWooDecisionController extends AbstractController
{
    public function __construct(
        private readonly DossierService $dossierService,
    ) {
    }

    #[Route('/dossier/{dossierId}', name: 'app_legacy_dossier_detail', methods: ['GET'], priority: -1)]
    public function detail(
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier,
    ): RedirectResponse {
        return $this->redirectToRoute(
            'app_woodecision_detail',
            [
                'prefix' => $dossier->getDocumentPrefix(),
                'dossierId' => $dossier->getDossierNr(),
            ],
            301,
        );
    }

    #[Route('/dossier/{dossierId}/batch', name: 'app_legacy_dossier_batch', methods: ['POST'], priority: -1)]
    public function createBatch(
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier,
    ): RedirectResponse {
        if (! $this->dossierService->isViewingAllowed($dossier)) {
            throw $this->createNotFoundException('Dossier not found');
        }

        return $this->redirectToRoute(
            'app_woodecision_batch',
            [
                'prefix' => $dossier->getDocumentPrefix(),
                'dossierId' => $dossier->getDossierNr(),
            ],
            301,
        );
    }

    #[Route('/dossier/{dossierId}/batch/{batchId}', name: 'app_legacy_dossier_batch_detail', methods: ['GET'], priority: -1)]
    public function batch(
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier,
        #[MapEntity(mapping: ['batchId' => 'id'])] BatchDownload $batch,
    ): RedirectResponse {
        if (! $this->dossierService->isViewingAllowed($dossier) || $batch->getEntity() !== $dossier) {
            throw $this->createNotFoundException('Dossier not found');
        }

        return $this->redirectToRoute(
            'app_woodecision_batch_detail',
            [
                'prefix' => $dossier->getDocumentPrefix(),
                'dossierId' => $dossier->getDossierNr(),
                'batchId' => $batch->getId(),
            ],
            301,
        );
    }

    #[Cache(public: true, maxage: 172800, mustRevalidate: true)]
    #[Route('/dossier/{dossierId}/batch/{batchId}/download', name: 'app_legacy_dossier_batch_download', methods: ['GET'], priority: -1)]
    public function batchDownload(
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier,
        #[MapEntity(mapping: ['batchId' => 'id'])] BatchDownload $batch,
    ): RedirectResponse {
        if (! $this->dossierService->isViewingAllowed($dossier) || $batch->getEntity() !== $dossier) {
            throw $this->createNotFoundException('Dossier not found');
        }

        return $this->redirectToRoute(
            'app_woodecision_batch_download',
            [
                'prefix' => $dossier->getDocumentPrefix(),
                'dossierId' => $dossier->getDossierNr(),
                'batchId' => $batch->getId(),
            ],
            301,
        );
    }

    #[Route('/dossier/{dossierId}/inventory/download', name: 'app_legacy_dossier_inventory_download', methods: ['GET'], priority: -1)]
    public function downloadInventory(
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier
    ): RedirectResponse {
        if (! $this->dossierService->isViewingAllowed($dossier)) {
            throw $this->createNotFoundException('Dossier not found');
        }

        return $this->redirectToRoute(
            'app_woodecision_inventory_download',
            [
                'prefix' => $dossier->getDocumentPrefix(),
                'dossierId' => $dossier->getDossierNr(),
            ],
            301,
        );
    }

    #[Route('/dossier/{dossierId}/decision/download', name: 'app_legacy_dossier_decision_download', methods: ['GET'], priority: -1)]
    public function downloadDecision(
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier
    ): RedirectResponse {
        if (! $this->dossierService->isViewingAllowed($dossier)) {
            throw $this->createNotFoundException('Dossier not found');
        }

        return $this->redirectToRoute(
            'app_woodecision_decision_download',
            [
                'prefix' => $dossier->getDocumentPrefix(),
                'dossierId' => $dossier->getDossierNr(),
            ],
            301,
        );
    }
}
