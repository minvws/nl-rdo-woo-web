<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\BatchDownload;
use App\Entity\Dossier;
use App\Message\GenerateArchiveMessage;
use App\Service\ArchiveService;
use App\Service\DossierService;
use App\Service\DownloadResponseHelper;
use App\Service\Search\Model\Config;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DossierController extends AbstractController
{
    protected const MAX_ITEMS_PER_PAGE = 100;

    public function __construct(
        private readonly EntityManagerInterface $doctrine,
        private readonly MessageBusInterface $messageBus,
        private readonly DossierService $dossierService,
        private readonly PaginatorInterface $paginator,
        private readonly ArchiveService $archiveService,
        private readonly DownloadResponseHelper $downloadHelper,
    ) {
    }

    #[Cache(public: true, maxage: 3600, mustRevalidate: true)]
    #[Route('/dossiers', name: 'app_dossier_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->redirectToRoute('app_search', ['type' => Config::TYPE_DOSSIER]);
    }

    #[Cache(public: true, maxage: 3600, mustRevalidate: true)]
    #[Route('/dossier/{dossierId}', name: 'app_dossier_detail', methods: ['GET'])]
    public function detail(
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier,
        Breadcrumbs $breadcrumbs,
        Request $request,
    ): Response {
        $breadcrumbs->addRouteItem('Home', 'app_home');
        $breadcrumbs->addItem('Dossier');

        if (! $this->dossierService->isViewingAllowed($dossier)) {
            throw $this->createNotFoundException('Dossier not found');
        }

        // Split the documents by judgement for display purposes
        $publicDocs = [];
        $notPublicDocs = [];
        foreach ($dossier->getDocuments() as $document) {
            if ($document->getJudgement()?->isAtLeastPartialPublic()) {
                $publicDocs[] = $document;
            } else {
                $notPublicDocs[] = $document;
            }
        }

        $publicPagination = $this->paginator->paginate(
            $publicDocs,
            $request->query->getInt('pu', 1),
            self::MAX_ITEMS_PER_PAGE,
            ['pageParameterName' => 'pu'],
        );
        $notPublicPagination = $this->paginator->paginate(
            $notPublicDocs,
            $request->query->getInt('pn', 1),
            self::MAX_ITEMS_PER_PAGE,
            ['pageParameterName' => 'pn'],
        );

        return $this->render('dossier/details.html.twig', [
            'dossier' => $dossier,
            'public_docs' => $publicPagination,
            'not_public_docs' => $notPublicPagination,
        ]);
    }

    #[Route('/dossier/{dossierId}/batch', name: 'app_dossier_batch', methods: ['POST'])]
    public function createBatch(
        Request $request,
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier,
    ): Response {
        if (! $this->dossierService->isViewingAllowed($dossier)) {
            throw $this->createNotFoundException('Dossier not found');
        }

        $docs = $request->request->all()['doc'] ?? [];
        if (! is_array($docs)) {
            $docs = [$docs];
        }

        $documents = [];
        foreach ($docs as $documentNr) {
            foreach ($dossier->getDocuments() as $document) {
                if ($document->getDocumentNr() === $documentNr) {
                    $documents[] = $document->getDocumentNr();
                    break;
                }
            }
        }

        // If a batch already exists with the given documents, redirect to that batch.
        $batch = $this->archiveService->archiveExists($dossier, $documents);
        if ($batch) {
            return $this->redirectToRoute('app_dossier_batch_detail', [
                'dossierId' => $dossier->getDossierNr(),
                'batchId' => $batch->getId(),
            ]);
        }

        $batch = new BatchDownload();
        $batch->setStatus(BatchDownload::STATUS_PENDING);
        $batch->setDossier($dossier);
        $batch->setDownloaded(0);
        $batch->setExpiration(new \DateTimeImmutable('+48 hours'));
        $batch->setDocuments($documents);

        $this->doctrine->persist($batch);
        $this->doctrine->flush();

        // Dispatch message to generate archive
        $this->messageBus->dispatch(new GenerateArchiveMessage($batch->getId()));

        return $this->redirectToRoute('app_dossier_batch_detail', [
            'dossierId' => $dossier->getDossierNr(),
            'batchId' => $batch->getId(),
        ]);
    }

    #[Route('/dossier/{dossierId}/batch/{batchId}', name: 'app_dossier_batch_detail', methods: ['GET'])]
    public function batch(
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier,
        #[MapEntity(mapping: ['batchId' => 'id'])] BatchDownload $batch,
        Breadcrumbs $breadcrumbs
    ): Response {
        $breadcrumbs->addRouteItem('Home', 'app_home');
        $breadcrumbs->addRouteItem('Dossier', 'app_dossier_detail', ['dossierId' => $dossier->getDossierNr()]);
        $breadcrumbs->addItem('Download');

        if (! $this->dossierService->isViewingAllowed($dossier)) {
            throw $this->createNotFoundException('Dossier not found');
        }

        return $this->render('dossier/batch.html.twig', [
            'dossier' => $dossier,
            'batch' => $batch,
        ]);
    }

    #[Cache(public: true, maxage: 172800, mustRevalidate: true)]
    #[Route('/dossier/{dossierId}/batch/{batchId}/download', name: 'app_dossier_batch_download', methods: ['GET'])]
    public function batchDownload(
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier,
        #[MapEntity(mapping: ['batchId' => 'id'])] BatchDownload $batch,
    ): Response {
        if (! $this->dossierService->isViewingAllowed($dossier)) {
            throw $this->createNotFoundException('Dossier not found');
        }

        if ($batch->getStatus() !== BatchDownload::STATUS_COMPLETED) {
            return $this->redirectToRoute('app_dossier_batch_detail', [
                'dossierId' => $dossier->getDossierNr(),
                'batchId' => $batch->getId(),
            ]);
        }

        $stream = $this->archiveService->getZipStream($batch);
        if (! $stream) {
            throw new NotFoundHttpException();
        }

        $response = new StreamedResponse();
        $response->headers->set('Content-Type', 'application/zip');
        $response->headers->set('Content-Length', $batch->getSize());
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $batch->getFilename() . '"');
        $response->setCallback(function () use ($stream) {
            fpassthru($stream);
        });

        return $response;
    }

    #[Cache(public: true, maxage: 172800, mustRevalidate: true)]
    #[Route('/dossier/{dossierId}/inventory/download', name: 'app_dossier_inventory_download', methods: ['GET'])]
    public function downloadInventory(
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier
    ): StreamedResponse {
        if (! $this->dossierService->isViewingAllowed($dossier)) {
            throw $this->createNotFoundException('Dossier not found');
        }

        return $this->downloadHelper->getResponseForEntityWithFileInfo($dossier->getInventory());
    }

    #[Cache(public: true, maxage: 172800, mustRevalidate: true)]
    #[Route('/dossier/{dossierId}/decision/download', name: 'app_dossier_decision_download', methods: ['GET'])]
    public function downloadDecision(
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier
    ): StreamedResponse {
        if (! $this->dossierService->isViewingAllowed($dossier)) {
            throw $this->createNotFoundException('Dossier not found');
        }

        return $this->downloadHelper->getResponseForEntityWithFileInfo($dossier->getDecisionDocument());
    }
}
