<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Document;
use App\Entity\Dossier;
use App\Service\Search\SearchService;
use App\Service\Storage\DocumentStorageService;
use App\Service\Storage\ThumbnailStorageService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

class DocumentController extends AbstractController
{
    protected EntityManagerInterface $doctrine;
    protected DocumentStorageService $documentStorage;
    protected ThumbnailStorageService $thumbnailStorage;
    protected SearchService $searchService;
    protected TranslatorInterface $translator;

    public function __construct(
        EntityManagerInterface $doctrine,
        DocumentStorageService $documentStorage,
        ThumbnailStorageService $thumbnailStorage,
        SearchService $searchService,
        TranslatorInterface $translator
    ) {
        $this->doctrine = $doctrine;
        $this->documentStorage = $documentStorage;
        $this->thumbnailStorage = $thumbnailStorage;
        $this->searchService = $searchService;
        $this->translator = $translator;
    }

    #[Route('/dossier/{dossierId}/document/{documentId}', name: 'app_document_detail', methods: ['GET'])]
    public function detail(
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier,
        #[MapEntity(mapping: ['documentId' => 'documentNr'])] Document $document,
        Breadcrumbs $breadcrumbs
    ): Response {
        $breadcrumbs->addRouteItem('Home', 'app_home');
        $breadcrumbs->addRouteItem('Dossier', 'app_dossier_detail', ['dossierId' => $dossier->getDossierNr()]);
        $breadcrumbs->addItem('Document');

        if (! $dossier->getDocuments()->contains($document)) {
            throw new NotFoundHttpException('Document not found');
        }

        $thread = $this->doctrine->getRepository(Document::class)->findBy(['threadId' => $document->getThreadId()], ['documentDate' => 'ASC']);
        $family = $this->doctrine->getRepository(Document::class)->findBy(['familyId' => $document->getFamilyId()], ['documentDate' => 'ASC']);

        // This could be easier with a criteria
        $family = array_filter($family, function (Document $doc) use ($document) {
            return $doc->getDocumentNr() !== $document->getDocumentNr();
        });

        return $this->render('document/details.html.twig', [
            'ingested' => $this->searchService->isIngested($document),
            'dossier' => $dossier,
            'document' => $document,
            'thread' => $thread,
            'family' => $family,
        ]);
    }

    #[Route('/dossier/{dossierId}/download/{documentId}', name: 'app_document_download', methods: ['GET'])]
    public function download(
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier,
        #[MapEntity(mapping: ['documentId' => 'documentNr'])] Document $document
    ): StreamedResponse {
        if (! $dossier->getDocuments()->contains($document)) {
            throw new NotFoundHttpException('Document not found');
        }

        $stream = $this->documentStorage->retrieveResourceDocument($document);
        if (! $stream) {
            throw new NotFoundHttpException();
        }

        // @todo: caching headers et al
        $response = new StreamedResponse();
        $response->headers->set('Content-Type', $document->getMimetype());

        $response->setCallback(function () use ($stream) {
            fpassthru($stream);
        });

        return $response;
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
        if (! $dossier->getDocuments()->contains($document)) {
            throw new NotFoundHttpException('Document not found');
        }

        $content = $this->searchService->getPageContent($document, intval($pageNr));
        if (! $content) {
            return new Response($this->translator->trans('No content found for this page'));
        }

        return new Response('<pre>' . trim($content) . '</pre>');
    }

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
        if (! $dossier->getDocuments()->contains($document)) {
            throw new NotFoundHttpException('Document not found');
        }

        // No file found (yet), just the document record
        if ($document->getFilepath() == null || ! $document->isUploaded()) {
            throw new NotFoundHttpException();
        }

        $stream = $this->documentStorage->retrieveResourcePage($document, intval($pageNr));
        if (! $stream) {
            throw new NotFoundHttpException();
        }

        // @todo: caching headers et al
        $response = new StreamedResponse(function () use ($stream) {
            fpassthru($stream);
        });
        $response->headers->set('Content-Type', $document->getMimetype());

        return $response;
    }

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
        if (! $dossier->getDocuments()->contains($document)) {
            throw new NotFoundHttpException('Document not found');
        }

        $stream = $this->thumbnailStorage->retrieveResource($document, intval($pageNr));
        if (! $stream) {
            // Display default placeholder thumbnail if we haven't found a thumbnail for given document/pageNr
            $path = sprintf('%s/%s', $this->getParameter('kernel.project_dir') . '/public', 'placeholder.png');
            $stream = fopen($path, 'rb');
            if (! $stream) {
                throw new NotFoundHttpException();
            }
        }

        // @todo: caching headers et al
        $response = new StreamedResponse();
        $response->headers->set('Content-Type', 'image/png');

        $response->setCallback(function () use ($stream) {
            fpassthru($stream);
        });

        return $response;
    }
}
