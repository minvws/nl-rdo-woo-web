<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Document;
use App\Entity\Dossier;
use App\Repository\DocumentRepository;
use App\Service\DossierService;
use App\Service\DownloadResponseHelper;
use App\Service\Search\SearchService;
use App\Service\Storage\DocumentStorageService;
use App\Service\Storage\ThumbnailStorageService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DocumentController extends AbstractController
{
    public function __construct(
        private readonly DocumentStorageService $documentStorage,
        private readonly ThumbnailStorageService $thumbnailStorage,
        private readonly SearchService $searchService,
        private readonly TranslatorInterface $translator,
        private readonly DocumentRepository $documentRepository,
        private readonly DossierService $dossierService,
        private readonly DownloadResponseHelper $downloadHelper,
    ) {
    }

    #[Cache(public: true, maxage: 3600, mustRevalidate: true)]
    #[Route('/dossier/{dossierId}/document/{documentId}', name: 'app_document_detail', methods: ['GET'])]
    public function detail(
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier,
        #[MapEntity(mapping: ['documentId' => 'documentNr'])] Document $document,
        Breadcrumbs $breadcrumbs
    ): Response {
        $breadcrumbs->addRouteItem('Home', 'app_home');
        $breadcrumbs->addRouteItem('Dossier', 'app_dossier_detail', ['dossierId' => $dossier->getDossierNr()]);
        $breadcrumbs->addItem('Document');

        if (! $this->dossierService->isViewingAllowed($dossier, $document)) {
            throw $this->createNotFoundException('Document or dossier not found');
        }

        if (! $dossier->getDocuments()->contains($document)) {
            throw new NotFoundHttpException('Document not found in dossier');
        }

        return $this->render('document/details.html.twig', [
            'ingested' => $this->searchService->isIngested($document),
            'dossier' => $dossier,
            'document' => $document,
            'thread' => $this->documentRepository->getRelatedDocumentsByThread($document),
            'family' => $this->documentRepository->getRelatedDocumentsByFamily($document),
        ]);
    }

    #[Cache(public: true, maxage: 3600, mustRevalidate: true)]
    #[Route('/dossier/{dossierId}/download/{documentId}', name: 'app_document_download', methods: ['GET'])]
    public function download(
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier,
        #[MapEntity(mapping: ['documentId' => 'documentNr'])] Document $document
    ): StreamedResponse {
        if (! $this->dossierService->isViewingAllowed($dossier, $document)) {
            throw $this->createNotFoundException('Document or dossier not found');
        }

        if ($document->isWithdrawn() || ! $dossier->getDocuments()->contains($document)) {
            throw new NotFoundHttpException('Document not found in dossier');
        }

        return $this->downloadHelper->getResponseForEntityWithFileInfo($document, false);
    }

    #[Route(
        '/dossier/{dossierId}/download/{documentId}/{pageNr}/_text',
        name: 'app_document_text',
        requirements: ['pageNr' => '\d+'],
        methods: ['GET']
    )]
    public function debugPage(
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier,
        #[MapEntity(mapping: ['documentId' => 'documentNr'])] Document $document,
        string $pageNr
    ): Response {
        if (! $this->dossierService->isViewingAllowed($dossier, $document)) {
            throw $this->createNotFoundException('Document or dossier not found');
        }

        if ($document->isWithdrawn() || ! $dossier->getDocuments()->contains($document)) {
            throw new NotFoundHttpException('Document not found in dossier');
        }

        $content = $this->searchService->getPageContent($document, intval($pageNr));
        if (! $content) {
            return new Response($this->translator->trans('No content found for this page'));
        }

        return new Response('<pre>' . trim($content) . '</pre>');
    }

    #[Cache(public: true, maxage: 3600, mustRevalidate: true)]
    #[Route(
        '/dossier/{dossierId}/download/{documentId}/{pageNr}',
        name: 'app_document_download_page',
        requirements: ['pageNr' => '\d+'],
        methods: ['GET']
    )]
    public function downloadPage(
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier,
        #[MapEntity(mapping: ['documentId' => 'documentNr'])] Document $document,
        string $pageNr
    ): StreamedResponse {
        if (! $this->dossierService->isViewingAllowed($dossier, $document)) {
            throw $this->createNotFoundException('Document or dossier not found');
        }

        if ($document->isWithdrawn() || ! $dossier->getDocuments()->contains($document)) {
            throw new NotFoundHttpException('Document not found in dossier');
        }

        // No file found (yet), just the document record
        $file = $document->getFileInfo();
        if ($file->getPath() === null || ! $file->isUploaded()) {
            throw new NotFoundHttpException();
        }

        $stream = $this->documentStorage->retrieveResourcePage($document, intval($pageNr));
        if (! $stream) {
            throw new NotFoundHttpException();
        }

        $response = new StreamedResponse();
        $response->headers->set('Content-Type', $file->getMimetype());
        $response->headers->set('Content-Length', (string) $file->getSize());
        $response->headers->set('Last-Modified', $document->getUpdatedAt()->format('D, d M Y H:i:s') . ' GMT');
        $response->setCallback(function () use ($stream) {
            fpassthru($stream);
        });

        return $response;
    }

    #[Cache(public: true, maxage: 3600, mustRevalidate: true)]
    #[Route(
        '/dossier/{dossierId}/thumbnail/{documentId}/{pageNr}',
        name: 'app_document_thumbnail',
        requirements: ['pageNr' => '\d+'],
        methods: ['GET']
    )]
    public function thumbnailPage(
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier,
        #[MapEntity(mapping: ['documentId' => 'documentNr'])] Document $document,
        string $pageNr
    ): StreamedResponse {
        if (! $this->dossierService->isViewingAllowed($dossier, $document)) {
            throw $this->createNotFoundException('Document or dossier not found');
        }

        if ($document->isWithdrawn() || ! $dossier->getDocuments()->contains($document)) {
            throw new NotFoundHttpException('Document not found in dossier');
        }

        $fileSize = $this->thumbnailStorage->fileSize($document, intval($pageNr));
        $stream = $this->thumbnailStorage->retrieveResource($document, intval($pageNr));
        if (! $stream) {
            // Display default placeholder thumbnail if we haven't found a thumbnail for given document/pageNr
            $path = sprintf('%s/%s', $this->getParameter('kernel.project_dir') . '/public', 'placeholder.png');
            $fileSize = filesize($path);
            $stream = fopen($path, 'rb');
            if (! $stream) {
                throw new NotFoundHttpException();
            }
        }

        $response = new StreamedResponse();
        $response->headers->set('Content-Type', 'image/png');
        $response->headers->set('Content-Length', (string) $fileSize);
        $response->setCallback(function () use ($stream) {
            fpassthru($stream);
        });

        return $response;
    }
}
