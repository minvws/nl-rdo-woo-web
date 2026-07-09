<?php

declare(strict_types=1);

namespace WooMinVWS\Controller;

use Shared\Domain\Publication\BatchDownload\BatchDownload;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Exception\ViewingNotAllowedException;
use Shared\Service\Security\DossierVoter;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Routing\Attribute\Route;

// Redirects old dossier urls (without prefixes in the url) to the new urls
class LegacyWooDecisionController extends AbstractController
{
    #[Route('/dossier/{dossierNumber}', name: 'app_legacy_dossier_detail', methods: ['GET'], priority: -1)]
    public function detail(
        #[MapEntity(mapping: ['dossierNumber' => 'dossierNumber'])] WooDecision $dossier,
    ): RedirectResponse {
        $this->denyAccessUnlessGranted(DossierVoter::VIEW, $dossier);

        return $this->redirectToRoute(
            'app_woodecision_detail',
            [
                'prefix' => $dossier->getDocumentPrefix(),
                'dossierNumber' => $dossier->getDossierNumber(),
            ],
            301,
        );
    }

    #[Route('/dossier/{dossierNumber}/batch', name: 'app_legacy_dossier_batch', methods: ['POST'], priority: -1)]
    public function createBatch(
        #[MapEntity(mapping: ['dossierNumber' => 'dossierNumber'])] WooDecision $dossier,
    ): RedirectResponse {
        $this->denyAccessUnlessGranted(DossierVoter::VIEW, $dossier);

        return $this->redirectToRoute(
            'app_woodecision_batch',
            [
                'prefix' => $dossier->getDocumentPrefix(),
                'dossierNumber' => $dossier->getDossierNumber(),
            ],
            301,
        );
    }

    #[Route('/dossier/{dossierNumber}/batch/{batchId}', name: 'app_legacy_dossier_batch_detail', methods: ['GET'], priority: -1)]
    public function batch(
        #[MapEntity(mapping: ['dossierNumber' => 'dossierNumber'])] WooDecision $dossier,
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
                'dossierNumber' => $dossier->getDossierNumber(),
                'batchId' => $batch->getId(),
            ],
            301,
        );
    }

    #[Cache(public: true, maxage: 600, mustRevalidate: true)]
    #[Route('/dossier/{dossierNumber}/batch/{batchId}/download', name: 'app_legacy_dossier_batch_download', methods: ['GET'], priority: -1)]
    public function batchDownload(
        #[MapEntity(mapping: ['dossierNumber' => 'dossierNumber'])] WooDecision $dossier,
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
                'dossierNumber' => $dossier->getDossierNumber(),
                'batchId' => $batch->getId(),
            ],
            301,
        );
    }

    #[Route('/dossier/{dossierNumber}/decision/download', name: 'app_legacy_dossier_decision_download', methods: ['GET'], priority: -1)]
    public function downloadDecision(
        #[MapEntity(mapping: ['dossierNumber' => 'dossierNumber'])] WooDecision $dossier,
    ): RedirectResponse {
        $this->denyAccessUnlessGranted(DossierVoter::VIEW, $dossier);

        return $this->redirectToRoute(
            'app_woodecision_document_detail',
            [
                'prefix' => $dossier->getDocumentPrefix(),
                'dossierNumber' => $dossier->getDossierNumber(),
            ],
            301,
        );
    }
}
