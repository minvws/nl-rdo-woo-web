<?php

declare(strict_types=1);

namespace App\Controller\Public\Dossier\WooDecision;

use App\Domain\Publication\Dossier\Type\WooDecision\ViewModel\DocumentViewFactory;
use App\Domain\Publication\Dossier\Type\WooDecision\ViewModel\WooDecisionViewFactory;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Entity\Document;
use App\Entity\Dossier;
use App\Repository\DocumentRepository;
use App\Service\DossierService;
use App\Service\DownloadResponseHelper;
use App\Service\Search\SearchService;
use App\Service\Storage\DocumentStorageService;
use App\Service\Storage\ThumbnailStorageService;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
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
        private readonly DocumentRepository $documentRepository,
        private readonly DossierService $dossierService,
        private readonly DownloadResponseHelper $downloadHelper,
        private readonly PaginatorInterface $paginator,
        private readonly WooDecisionViewFactory $wooDecisionViewFactory,
        private readonly DocumentViewFactory $documentViewFactory,
    ) {
    }

    #[Cache(public: true, maxage: 3600, mustRevalidate: true)]
    #[Route('/dossier/{prefix}/{dossierId}/document/{documentId}', name: 'app_document_detail', methods: ['GET'])]
    public function detail(
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])] WooDecision $dossier,
        #[MapEntity(expr: 'repository.findOneByDossierNrAndDocumentNr(prefix, dossierId, documentId)')] Document $document,
        Breadcrumbs $breadcrumbs,
        Request $request,
    ): Response {
        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem('global.decision', 'app_woodecision_detail', [
            'prefix' => $dossier->getDocumentPrefix(),
            'dossierId' => $dossier->getDossierNr(),
        ]);
        $breadcrumbs->addItem('global.document');

        if (! $this->dossierService->isViewingAllowed($dossier, $document)) {
            throw $this->createNotFoundException('Document or dossier not found');
        }

        /** @var PaginationInterface<array-key,Document> $threadDocPaginator */
        $threadDocPaginator = $this->paginator->paginate(
            $this->documentRepository->getRelatedDocumentsByThread($dossier, $document),
            $request->query->getInt('pp', 1),
            100,
            [
                'pageParameterName' => 'pp',
                'sortFieldParameterName' => 'ps',
                'sortDirectionParameterName' => 'psd',
            ],
        );

        /** @var PaginationInterface<array-key,Document> $familyDocPaginator */
        $familyDocPaginator = $this->paginator->paginate(
            $this->documentRepository->getRelatedDocumentsByFamily($dossier, $document),
            $request->query->getInt('fp', 1),
            100,
            [
                'pageParameterName' => 'fp',
                'sortFieldParameterName' => 'fs',
                'sortDirectionParameterName' => 'fsd',
            ],
        );

        return $this->render('document/details.html.twig', [
            'dossier' => $this->wooDecisionViewFactory->make($dossier),
            'document' => $this->documentViewFactory->make($document),
            'thread' => $threadDocPaginator,
            'family' => $familyDocPaginator,
        ]);
    }

    #[Cache(public: true, maxage: 3600, mustRevalidate: true)]
    #[Route('/dossier/{prefix}/{dossierId}/download/{documentId}', name: 'app_document_download', methods: ['GET'])]
    public function download(
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])] Dossier $dossier,
        #[MapEntity(expr: 'repository.findOneByDossierNrAndDocumentNr(prefix, dossierId, documentId)')] Document $document,
    ): StreamedResponse {
        if (! $this->dossierService->isViewingAllowed($dossier, $document)) {
            throw $this->createNotFoundException('Document or dossier not found');
        }

        if (! $document->shouldBeUploaded()) {
            throw new NotFoundHttpException('Document has no download');
        }

        return $this->downloadHelper->getResponseForEntityWithFileInfo($document, false);
    }

    #[Route(
        '/dossier/{prefix}/{dossierId}/download/{documentId}/{pageNr}/_text',
        name: 'app_document_text',
        requirements: ['pageNr' => '\d+'],
        methods: ['GET']
    )]
    public function debugPage(
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])] Dossier $dossier,
        #[MapEntity(expr: 'repository.findOneByDossierNrAndDocumentNr(prefix, dossierId, documentId)')] Document $document,
        string $pageNr
    ): Response {
        if (! $this->dossierService->isViewingAllowed($dossier, $document)) {
            throw $this->createNotFoundException('Document or dossier not found');
        }

        $content = $this->searchService->getPageContent($document, intval($pageNr));
        if (! $content) {
            throw $this->createNotFoundException('No content found for this page');
        }

        return new Response('<pre>' . trim($content) . '</pre>');
    }

    #[Cache(public: true, maxage: 3600, mustRevalidate: true)]
    #[Route(
        '/dossier/{prefix}/{dossierId}/download/{documentId}/{pageNr}',
        name: 'app_document_download_page',
        requirements: ['pageNr' => '\d+'],
        methods: ['GET']
    )]
    public function downloadPage(
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])] Dossier $dossier,
        #[MapEntity(expr: 'repository.findOneByDossierNrAndDocumentNr(prefix, dossierId, documentId)')] Document $document,
        string $pageNr
    ): StreamedResponse {
        if (! $this->dossierService->isViewingAllowed($dossier, $document)) {
            throw $this->createNotFoundException('Document or dossier not found');
        }

        if (! $document->shouldBeUploaded()) {
            throw new NotFoundHttpException('Document has no download');
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
        '/dossier/{prefix}/{dossierId}/thumbnail/{documentId}/{pageNr}',
        name: 'app_document_thumbnail',
        requirements: ['pageNr' => '\d+'],
        methods: ['GET']
    )]
    public function thumbnailPage(
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])] Dossier $dossier,
        #[MapEntity(expr: 'repository.findOneByDossierNrAndDocumentNr(prefix, dossierId, documentId)')] Document $document,
        string $pageNr
    ): StreamedResponse {
        if (! $this->dossierService->isViewingAllowed($dossier, $document)) {
            throw $this->createNotFoundException('Document or dossier not found');
        }

        if (! $document->shouldBeUploaded()) {
            throw new NotFoundHttpException('Document has no thumbnail');
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
