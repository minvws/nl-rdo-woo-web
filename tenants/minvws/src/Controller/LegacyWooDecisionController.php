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
    public const string ROUTE_NAME_LEGACY_DOSSIER_DETAIL = 'app_legacy_dossier_detail';
    public const string ROUTE_NAME_LEGACY_DOSSIER_BATCH = 'app_legacy_dossier_batch';
    public const string ROUTE_NAME_LEGACY_DOSSIER_BATCH_DETAIL = 'app_legacy_dossier_batch_detail';
    public const string ROUTE_NAME_LEGACY_DOSSIER_BATCH_DOWNLOAD = 'app_legacy_dossier_batch_download';
    public const string ROUTE_NAME_LEGACY_DOSSIER_DECISION_DOWNLOAD = 'app_legacy_dossier_decision_download';

    #[Route('/dossier/{dossierNumber}', self::ROUTE_NAME_LEGACY_DOSSIER_DETAIL, methods: ['GET'], priority: -1)]
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

    #[Route('/dossier/{dossierNumber}/batch', self::ROUTE_NAME_LEGACY_DOSSIER_BATCH, methods: ['POST'], priority: -1)]
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

    #[Route('/dossier/{dossierNumber}/batch/{batchId}', self::ROUTE_NAME_LEGACY_DOSSIER_BATCH_DETAIL, methods: ['GET'], priority: -1)]
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
    #[Route('/dossier/{dossierNumber}/batch/{batchId}/download', self::ROUTE_NAME_LEGACY_DOSSIER_BATCH_DOWNLOAD, methods: ['GET'], priority: -1)]
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

    #[Route('/dossier/{dossierNumber}/decision/download', self::ROUTE_NAME_LEGACY_DOSSIER_DECISION_DOWNLOAD, methods: ['GET'], priority: -1)]
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
