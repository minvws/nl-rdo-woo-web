<?php

declare(strict_types=1);

namespace App\Controller\Public\Dossier\WooDecision;

use App\Domain\Publication\BatchDownload\BatchDownload;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Exception\ViewingNotAllowedException;
use App\Service\Security\DossierVoter;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Routing\Annotation\Route;

// Redirects old dossier urls (without prefixes in the url) to the new urls
class LegacyWooDecisionController extends AbstractController
{
    #[Route('/dossier/{dossierId}', name: 'app_legacy_dossier_detail', methods: ['GET'], priority: -1)]
    public function detail(
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] WooDecision $dossier,
    ): RedirectResponse {
        $this->denyAccessUnlessGranted(DossierVoter::VIEW, $dossier);

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
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] WooDecision $dossier,
    ): RedirectResponse {
        $this->denyAccessUnlessGranted(DossierVoter::VIEW, $dossier);

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
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] WooDecision $dossier,
        #[MapEntity(mapping: ['batchId' => 'id'])] BatchDownload $batch,
    ): RedirectResponse {
        $this->denyAccessUnlessGranted(DossierVoter::VIEW, $dossier);
        if ($batch->getDossier() !== $dossier) {
            throw ViewingNotAllowedException::forDossier();
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

    #[Cache(public: true, maxage: 600, mustRevalidate: true)]
    #[Route('/dossier/{dossierId}/batch/{batchId}/download', name: 'app_legacy_dossier_batch_download', methods: ['GET'], priority: -1)]
    public function batchDownload(
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] WooDecision $dossier,
        #[MapEntity(mapping: ['batchId' => 'id'])] BatchDownload $batch,
    ): RedirectResponse {
        $this->denyAccessUnlessGranted(DossierVoter::VIEW, $dossier);
        if ($batch->getDossier() !== $dossier) {
            throw ViewingNotAllowedException::forDossier();
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

    #[Route('/dossier/{dossierId}/decision/download', name: 'app_legacy_dossier_decision_download', methods: ['GET'], priority: -1)]
    public function downloadDecision(
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] WooDecision $dossier,
    ): RedirectResponse {
        $this->denyAccessUnlessGranted(DossierVoter::VIEW, $dossier);

        return $this->redirectToRoute(
            'app_woodecision_document_detail',
            [
                'prefix' => $dossier->getDocumentPrefix(),
                'dossierId' => $dossier->getDossierNr(),
            ],
            301,
        );
    }
}
