<?php

declare(strict_types=1);

namespace App\Controller\Public\Dossier\WooDecision;

use App\Entity\Document;
use App\Entity\Dossier;
use App\Exception\ViewingNotAllowedException;
use App\Service\DossierService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

// Redirects old dossier urls (without prefixes in the url) to the new urls
class LegacyDocumentController extends AbstractController
{
    public function __construct(
        private readonly DossierService $dossierService,
    ) {
    }

    #[Route('/dossier/{dossierId}/document/{documentId}', name: 'app_legacy_document_detail', methods: ['GET'], priority: -1)]
    public function detail(
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier,
        #[MapEntity(mapping: ['documentId' => 'documentNr'])] Document $document,
    ): RedirectResponse {
        $this->validateAccess($dossier, $document);

        return $this->redirectToRoute(
            'app_document_detail',
            [
                'prefix' => $dossier->getDocumentPrefix(),
                'dossierId' => $dossier->getDossierNr(),
                'documentId' => $document->getDocumentNr(),
            ],
            301,
        );
    }

    #[Route('/dossier/{dossierId}/download/{documentId}', name: 'app_legacy_document_download', methods: ['GET'], priority: -1)]
    public function download(
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier,
        #[MapEntity(mapping: ['documentId' => 'documentNr'])] Document $document,
    ): RedirectResponse {
        $this->validateAccess($dossier, $document);

        return $this->redirectToRoute(
            'app_document_download',
            [
                'prefix' => $dossier->getDocumentPrefix(),
                'dossierId' => $dossier->getDossierNr(),
                'documentId' => $document->getDocumentNr(),
            ],
            301,
        );
    }

    #[Route(
        '/dossier/{dossierId}/download/{documentId}/{pageNr}',
        name: 'app_legacy_document_download_page',
        requirements: ['pageNr' => '\d+'],
        methods: ['GET'],
        priority: -1,
    )]
    public function downloadPage(
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier,
        #[MapEntity(mapping: ['documentId' => 'documentNr'])] Document $document,
        string $pageNr,
    ): RedirectResponse {
        $this->validateAccess($dossier, $document);

        return $this->redirectToRoute(
            'app_document_download_page',
            [
                'prefix' => $dossier->getDocumentPrefix(),
                'dossierId' => $dossier->getDossierNr(),
                'documentId' => $document->getDocumentNr(),
                'pageNr' => $pageNr,
            ],
            301,
        );
    }

    #[Route(
        '/dossier/{dossierId}/thumbnail/{documentId}/{pageNr}',
        name: 'app_legacy_document_thumbnail',
        requirements: ['pageNr' => '\d+'],
        methods: ['GET'],
        priority: -1,
    )]
    public function thumbnailPage(
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier,
        #[MapEntity(mapping: ['documentId' => 'documentNr'])] Document $document,
        string $pageNr,
    ): RedirectResponse {
        $this->validateAccess($dossier, $document);

        return $this->redirectToRoute(
            'app_document_thumbnail',
            [
                'prefix' => $dossier->getDocumentPrefix(),
                'dossierId' => $dossier->getDossierNr(),
                'documentId' => $document->getDocumentNr(),
                'pageNr' => $pageNr,
            ],
            301,
        );
    }

    private function validateAccess(Dossier $dossier, Document $document): void
    {
        if (! $this->dossierService->isViewingAllowed($dossier, $document) || ! $document->getDossiers()->contains($dossier)) {
            throw ViewingNotAllowedException::forDossierOrDocument();
        }
    }
}
