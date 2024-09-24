<?php

declare(strict_types=1);

namespace App\Controller\Public\Dossier\WooDecision;

use App\Doctrine\DocumentConditions;
use App\Domain\Publication\Attachment\ViewModel\AttachmentViewFactory;
use App\Domain\Publication\Dossier\Type\WooDecision\ViewModel\WooDecisionViewFactory;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Search\Query\SearchType;
use App\Entity\BatchDownload;
use App\Entity\DecisionAttachment;
use App\Entity\Dossier;
use App\Exception\ViewingNotAllowedException;
use App\Repository\DocumentRepository;
use App\Service\BatchDownloadService;
use App\Service\DownloadResponseHelper;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Annotation\Route;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class WooDecisionController extends AbstractController
{
    protected const MAX_ITEMS_PER_PAGE = 100;

    public function __construct(
        private readonly PaginatorInterface $paginator,
        private readonly DownloadResponseHelper $downloadHelper,
        private readonly BatchDownloadService $batchDownloadService,
        private readonly DocumentRepository $documentRepository,
        private readonly WooDecisionViewFactory $wooDecisionViewFactory,
        private readonly AttachmentViewFactory $attachmentViewFactory,
    ) {
    }

    #[Cache(public: true, maxage: 3600, mustRevalidate: true)]
    #[Route('/dossiers', name: 'app_woodecision_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->redirectToRoute('app_search', ['type' => SearchType::DOSSIER->value]);
    }

    #[Cache(public: true, maxage: 3600, mustRevalidate: true)]
    #[Route('/dossier/{prefix}/{dossierId}', name: 'app_woodecision_detail', methods: ['GET'])]
    public function detail(
        #[ValueResolver('dossierWithAccessCheck')] WooDecision $dossier,
        Breadcrumbs $breadcrumbs,
        Request $request,
    ): Response {
        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addItem('global.decision');

        $docQuery = $this->documentRepository->getDossierDocumentsQueryBuilder($dossier);

        /** @var PaginationInterface<array-key,Dossier> $publicPagination */
        $publicPagination = $this->paginator->paginate(
            DocumentConditions::onlyPubliclyAvailable($docQuery),
            $request->query->getInt('pu', 1),
            self::MAX_ITEMS_PER_PAGE,
            ['pageParameterName' => 'pu'],
        );

        /** @var PaginationInterface<array-key,Dossier> $alreadyPublicPagination */
        $alreadyPublicPagination = $this->paginator->paginate(
            DocumentConditions::onlyAlreadyPublic($docQuery),
            $request->query->getInt('pa', 1),
            self::MAX_ITEMS_PER_PAGE,
            ['pageParameterName' => 'pa'],
        );

        /** @var PaginationInterface<array-key,Dossier> $notPublicPagination */
        $notPublicPagination = $this->paginator->paginate(
            DocumentConditions::notPubliclyAvailable($docQuery),
            $request->query->getInt('pn', 1),
            self::MAX_ITEMS_PER_PAGE,
            ['pageParameterName' => 'pn'],
        );

        /** @var PaginationInterface<array-key,Dossier> $notOnlinePagination */
        $notOnlinePagination = $this->paginator->paginate(
            DocumentConditions::notOnline($docQuery),
            $request->query->getInt('po', 1),
            self::MAX_ITEMS_PER_PAGE,
            ['pageParameterName' => 'po'],
        );

        return $this->render('dossier/details.html.twig', [
            'publicDocs' => $publicPagination,
            'alreadyPublicDocs' => $alreadyPublicPagination,
            'notPublicDocs' => $notPublicPagination,
            'notOnlineDocs' => $notOnlinePagination,
            'dossier' => $this->wooDecisionViewFactory->make($dossier),
            'attachments' => $this->attachmentViewFactory->makeCollection($dossier),
        ]);
    }

    #[Route('/dossier/{prefix}/{dossierId}/batch', name: 'app_woodecision_batch', methods: ['POST'])]
    public function createBatch(
        Request $request,
        #[ValueResolver('dossierWithAccessCheck')] Dossier $dossier,
    ): Response {
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

        $batch = $this->batchDownloadService->findOrCreate($dossier, $documents, count($documents) > 0);

        return $this->redirectToRoute('app_woodecision_batch_detail', [
            'prefix' => $dossier->getDocumentPrefix(),
            'dossierId' => $dossier->getDossierNr(),
            'batchId' => $batch->getId(),
        ]);
    }

    #[Route('/dossier/{prefix}/{dossierId}/batch/{batchId}', name: 'app_woodecision_batch_detail', methods: ['GET'])]
    public function batch(
        #[ValueResolver('dossierWithAccessCheck')] Dossier $dossier,
        #[MapEntity(mapping: ['batchId' => 'id'])] BatchDownload $batch,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem('global.decision', 'app_woodecision_detail', [
            'prefix' => $dossier->getDocumentPrefix(),
            'dossierId' => $dossier->getDossierNr(),
        ]);
        $breadcrumbs->addItem('public.global.download');

        if ($batch->getEntity() !== $dossier) {
            throw ViewingNotAllowedException::forDossier();
        }

        return $this->render('batchdownload/batch.html.twig', [
            'batch' => $batch,
            'pageTitle' => 'public.documents.archive.download',
            'download_path' => $this->generateUrl(
                'app_woodecision_batch_download',
                [
                    'prefix' => $dossier->getDocumentPrefix(),
                    'dossierId' => $dossier->getDossierNr(),
                    'batchId' => $batch->getId(),
                ]
            ),
        ]);
    }

    #[Cache(public: true, maxage: 172800, mustRevalidate: true)]
    #[Route('/dossier/{prefix}/{dossierId}/batch/{batchId}/download', name: 'app_woodecision_batch_download', methods: ['GET'])]
    public function batchDownload(
        #[ValueResolver('dossierWithAccessCheck')] Dossier $dossier,
        #[MapEntity(mapping: ['batchId' => 'id'])] BatchDownload $batch,
    ): Response {
        if ($batch->getEntity() !== $dossier) {
            throw ViewingNotAllowedException::forDossier();
        }

        if ($batch->getStatus() !== BatchDownload::STATUS_COMPLETED) {
            return $this->redirectToRoute('app_woodecision_batch_detail', [
                'prefix' => $dossier->getDocumentPrefix(),
                'dossierId' => $dossier->getDossierNr(),
                'batchId' => $batch->getId(),
            ]);
        }

        return $this->downloadHelper->getResponseForBatchDownload($batch);
    }

    #[Cache(public: true, maxage: 172800, mustRevalidate: true)]
    #[Route('/dossier/{prefix}/{dossierId}/inventory/download', name: 'app_woodecision_inventory_download', methods: ['GET'])]
    public function downloadInventory(
        #[ValueResolver('dossierWithAccessCheck')] Dossier $dossier,
    ): StreamedResponse {
        return $this->downloadHelper->getResponseForEntityWithFileInfo($dossier->getInventory());
    }

    #[Cache(public: true, maxage: 172800, mustRevalidate: true)]
    #[Route('/dossier/{prefix}/{dossierId}/decision/download', name: 'app_woodecision_decision_download', methods: ['GET'])]
    public function downloadDecision(
        #[ValueResolver('dossierWithAccessCheck')] Dossier $dossier,
    ): StreamedResponse {
        $decisionDocument = $dossier->getDecisionDocument();
        if (! $decisionDocument) {
            throw $this->createNotFoundException('Dossier decision not found');
        }

        return $this->downloadHelper->getResponseForEntityWithFileInfo(
            $decisionDocument,
            true,
            'decision-' . $dossier->getDossierNr() . '.' . $decisionDocument->getFileInfo()->getType()
        );
    }

    #[Cache(public: true, maxage: 172800, mustRevalidate: true)]
    #[Route(
        '/dossier/{prefix}/{dossierId}/decision-attachments/{attachmentId}/download',
        name: 'app_woodecision_decisionattachment_download',
        methods: ['GET'],
    )]
    public function decisionAttachmentDownload(
        #[ValueResolver('dossierWithAccessCheck')] Dossier $dossier,
        #[MapEntity(expr: 'repository.findForDossierByPrefixAndNr(prefix, dossierId, attachmentId)')]
        DecisionAttachment $attachment,
    ): Response {
        unset($dossier);

        return $this->downloadHelper->getResponseForEntityWithFileInfo($attachment);
    }

    #[Cache(public: true, maxage: 172800, mustRevalidate: true)]
    #[Route(
        '/dossier/{prefix}/{dossierId}/decision-attachments/{attachmentId}',
        name: 'app_woodecision_decisionattachment_detail',
        methods: ['GET'],
    )]
    public function decisionAttachmentDetail(
        #[ValueResolver('dossierWithAccessCheck')] WooDecision $dossier,
        #[MapEntity(expr: 'repository.findForDossierByPrefixAndNr(prefix, dossierId, attachmentId)')]
        DecisionAttachment $attachment,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $attachmentView = $this->attachmentViewFactory->make($dossier, $attachment);

        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem('global.decision', 'app_woodecision_detail', [
            'prefix' => $dossier->getDocumentPrefix(),
            'dossierId' => $dossier->getDossierNr(),
        ]);
        $breadcrumbs->addItem($attachmentView->name ?? '');

        return $this->render('dossier/decision-attachment.html.twig', [
            'dossier' => $this->wooDecisionViewFactory->make($dossier),
            'attachments' => $this->attachmentViewFactory->makeCollection($dossier),
            'attachment' => $attachmentView,
        ]);
    }
}
