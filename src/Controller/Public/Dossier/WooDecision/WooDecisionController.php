<?php

declare(strict_types=1);

namespace Shared\Controller\Public\Dossier\WooDecision;

use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Shared\Doctrine\DocumentConditions;
use Shared\Domain\Publication\Attachment\ViewModel\AttachmentViewFactory;
use Shared\Domain\Publication\BatchDownload\BatchDownload;
use Shared\Domain\Publication\BatchDownload\BatchDownloadScope;
use Shared\Domain\Publication\BatchDownload\BatchDownloadService;
use Shared\Domain\Publication\Dossier\FileProvider\DossierFileType;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Attachment\WooDecisionAttachment;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\MainDocument\WooDecisionMainDocument;
use Shared\Domain\Publication\Dossier\Type\WooDecision\ViewModel\WooDecisionViewFactory;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\ViewModel\DossierFileViewFactory;
use Shared\Domain\Publication\MainDocument\ViewModel\MainDocumentViewFactory;
use Shared\Exception\ViewingNotAllowedException;
use Shared\Service\DownloadResponseHelper;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Annotation\Route;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

/**
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
class WooDecisionController extends AbstractController
{
    protected const int MAX_ITEMS_PER_PAGE = 100;

    public function __construct(
        private readonly PaginatorInterface $paginator,
        private readonly DownloadResponseHelper $downloadHelper,
        private readonly BatchDownloadService $batchDownloadService,
        private readonly DocumentRepository $documentRepository,
        private readonly WooDecisionViewFactory $wooDecisionViewFactory,
        private readonly AttachmentViewFactory $attachmentViewFactory,
        private readonly DossierFileViewFactory $dossierFileViewFactory,
        private readonly MainDocumentViewFactory $mainDocumentViewFactory,
    ) {
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

        /** @var PaginationInterface<array-key,WooDecision> $publicPagination */
        $publicPagination = $this->paginator->paginate(
            DocumentConditions::onlyPubliclyAvailable($docQuery),
            $request->query->getInt('pu', 1),
            self::MAX_ITEMS_PER_PAGE,
            ['pageParameterName' => 'pu'],
        );

        /** @var PaginationInterface<array-key,WooDecision> $alreadyPublicPagination */
        $alreadyPublicPagination = $this->paginator->paginate(
            DocumentConditions::onlyAlreadyPublic($docQuery),
            $request->query->getInt('pa', 1),
            self::MAX_ITEMS_PER_PAGE,
            ['pageParameterName' => 'pa'],
        );

        /** @var PaginationInterface<array-key,WooDecision> $notPublicPagination */
        $notPublicPagination = $this->paginator->paginate(
            DocumentConditions::notPubliclyAvailable($docQuery),
            $request->query->getInt('pn', 1),
            self::MAX_ITEMS_PER_PAGE,
            ['pageParameterName' => 'pn'],
        );

        /** @var PaginationInterface<array-key,WooDecision> $notOnlinePagination */
        $notOnlinePagination = $this->paginator->paginate(
            DocumentConditions::notOnline($docQuery),
            $request->query->getInt('po', 1),
            self::MAX_ITEMS_PER_PAGE,
            ['pageParameterName' => 'po'],
        );

        return $this->render('public/dossier/woo-decision/details.html.twig', [
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
        #[ValueResolver('dossierWithAccessCheck')] WooDecision $dossier,
    ): Response {
        $batch = $this->batchDownloadService->findOrCreate(
            BatchDownloadScope::forWooDecision($dossier),
        );

        return $this->redirectToRoute('app_woodecision_batch_detail', [
            'prefix' => $dossier->getDocumentPrefix(),
            'dossierId' => $dossier->getDossierNr(),
            'batchId' => $batch->getId(),
        ]);
    }

    #[Route('/dossier/{prefix}/{dossierId}/batch/{batchId}', name: 'app_woodecision_batch_detail', methods: ['GET'])]
    public function batch(
        #[ValueResolver('dossierWithAccessCheck')] WooDecision $dossier,
        #[MapEntity(mapping: ['batchId' => 'id'])] BatchDownload $batch,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem('global.decision', 'app_woodecision_detail', [
            'prefix' => $dossier->getDocumentPrefix(),
            'dossierId' => $dossier->getDossierNr(),
        ]);
        $breadcrumbs->addItem('public.global.download');

        if ($batch->getDossier() !== $dossier) {
            throw ViewingNotAllowedException::forDossier();
        }

        return $this->render('public/dossier/woo-decision/shared/batch-download.html.twig', [
            'batch' => $batch,
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

    #[Route('/dossier/{prefix}/{dossierId}/batch/{batchId}/download', name: 'app_woodecision_batch_download', methods: ['GET'])]
    public function batchDownload(
        #[ValueResolver('dossierWithAccessCheck')] WooDecision $dossier,
        #[MapEntity(mapping: ['batchId' => 'id'])] BatchDownload $batch,
    ): Response {
        if ($batch->getDossier() !== $dossier) {
            throw ViewingNotAllowedException::forDossier();
        }

        if (! $batch->getStatus()->isDownloadable()) {
            return $this->redirectToRoute('app_woodecision_batch_detail', [
                'prefix' => $dossier->getDocumentPrefix(),
                'dossierId' => $dossier->getDossierNr(),
                'batchId' => $batch->getId(),
            ]);
        }

        return $this->downloadHelper->getResponseForBatchDownload($batch);
    }

    #[Cache(public: true, maxage: 600, mustRevalidate: true)]
    #[Route(
        '/dossier/{prefix}/{dossierId}/bijlage/{attachmentId}',
        name: 'app_woodecision_attachment_detail',
        methods: ['GET'],
    )]
    public function decisionAttachmentDetail(
        #[ValueResolver('dossierWithAccessCheck')] WooDecision $dossier,
        #[MapEntity(expr: 'repository.findForDossierByPrefixAndNr(prefix, dossierId, attachmentId)')]
        WooDecisionAttachment $attachment,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $attachmentView = $this->attachmentViewFactory->make($dossier, $attachment);

        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem('global.decision', 'app_woodecision_detail', [
            'prefix' => $dossier->getDocumentPrefix(),
            'dossierId' => $dossier->getDossierNr(),
        ]);
        $breadcrumbs->addItem($attachmentView->name ?? '');

        return $this->render('public/dossier/woo-decision/attachment.html.twig', [
            'dossier' => $this->wooDecisionViewFactory->make($dossier),
            'attachments' => $this->attachmentViewFactory->makeCollection($dossier),
            'attachment' => $attachmentView,
            'file' => $this->dossierFileViewFactory->make(
                $dossier,
                $attachment,
                DossierFileType::ATTACHMENT,
            ),
        ]);
    }

    #[Cache(maxage: 600, public: true, mustRevalidate: true)]
    #[Route(
        '/dossier/{prefix}/{dossierId}/document',
        name: 'app_woodecision_document_detail',
        methods: ['GET'],
    )]
    public function mainDocumentDetail(
        #[ValueResolver('dossierWithAccessCheck')] WooDecision $wooDecision,
        #[MapEntity(expr: 'repository.findForDossierByPrefixAndNr(prefix, dossierId)')]
        WooDecisionMainDocument $mainDocument,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $mainDocumentViewModel = $this->mainDocumentViewFactory->make($wooDecision, $mainDocument);

        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem('global.decision', 'app_woodecision_detail', [
            'prefix' => $wooDecision->getDocumentPrefix(),
            'dossierId' => $wooDecision->getDossierNr(),
        ]);
        $breadcrumbs->addItem($mainDocumentViewModel->name ?? '');

        return $this->render('public/dossier/woo-decision/document.html.twig', [
            'dossier' => $this->wooDecisionViewFactory->make($wooDecision),
            'attachments' => $this->attachmentViewFactory->makeCollection($wooDecision),
            'document' => $mainDocumentViewModel,
            'file' => $this->dossierFileViewFactory->make(
                $wooDecision,
                $mainDocument,
                DossierFileType::MAIN_DOCUMENT,
            ),
        ]);
    }
}
