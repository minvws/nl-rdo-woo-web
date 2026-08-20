<?php

declare(strict_types=1);

namespace Shared\Controller\Public\Dossier\WooDecision;

use Huluti\BreadcrumbsBundle\Model\Breadcrumbs;
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
use Shared\Domain\Publication\Dossier\Type\WooDecision\PublicationReason;
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
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Webmozart\Assert\Assert;

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
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Cache(public: true, maxage: 3600, mustRevalidate: true)]
    #[Route('/dossier/{documentPrefix}/{dossierNumber}', name: 'app_woodecision_detail', methods: ['GET'])]
    public function detail(
        #[ValueResolver('dossierWithAccessCheck')] WooDecision $wooDecision,
        Breadcrumbs $breadcrumbs,
        Request $request,
    ): Response {
        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addItem($this->getPublicationReason($wooDecision));

        $docQuery = $this->documentRepository->getDossierDocumentsQueryBuilder($wooDecision);

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
            'dossier' => $this->wooDecisionViewFactory->make($wooDecision),
            'attachments' => $this->attachmentViewFactory->makeCollection($wooDecision),
        ]);
    }

    #[Route('/dossier/{documentPrefix}/{dossierNumber}/batch', name: 'app_woodecision_batch', methods: ['POST'])]
    public function createBatch(
        #[ValueResolver('dossierWithAccessCheck')] WooDecision $wooDecision,
    ): Response {
        $batch = $this->batchDownloadService->findOrCreate(
            BatchDownloadScope::forWooDecision($wooDecision),
        );

        return $this->redirectToRoute('app_woodecision_batch_detail', [
            'documentPrefix' => $wooDecision->getDocumentPrefix(),
            'dossierNumber' => $wooDecision->getDossierNumber(),
            'batchId' => $batch->getId(),
        ]);
    }

    #[Route('/dossier/{documentPrefix}/{dossierNumber}/batch/{batchId}', name: 'app_woodecision_batch_detail', methods: ['GET'])]
    public function batch(
        #[ValueResolver('dossierWithAccessCheck')] WooDecision $wooDecision,
        #[MapEntity(mapping: ['batchId' => 'id'])] BatchDownload $batch,
        Breadcrumbs $breadcrumbs,
    ): Response {
        if ($batch->getDossier() !== $wooDecision) {
            throw ViewingNotAllowedException::forDossier();
        }

        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem($this->getPublicationReason($wooDecision), 'app_woodecision_detail', [
            'documentPrefix' => $wooDecision->getDocumentPrefix(),
            'dossierNumber' => $wooDecision->getDossierNumber(),
        ]);
        $breadcrumbs->addItem('public.global.download');

        return $this->render('public/dossier/woo-decision/shared/batch-download.html.twig', [
            'batch' => $batch,
            'download_path' => $this->generateUrl(
                'app_woodecision_batch_download',
                [
                    'documentPrefix' => $wooDecision->getDocumentPrefix(),
                    'dossierNumber' => $wooDecision->getDossierNumber(),
                    'batchId' => $batch->getId(),
                ],
            ),
        ]);
    }

    #[Route('/dossier/{documentPrefix}/{dossierNumber}/batch/{batchId}/download', name: 'app_woodecision_batch_download', methods: ['GET'])]
    public function batchDownload(
        #[ValueResolver('dossierWithAccessCheck')] WooDecision $wooDecision,
        #[MapEntity(mapping: ['batchId' => 'id'])] BatchDownload $batch,
    ): Response {
        if ($batch->getDossier() !== $wooDecision) {
            throw ViewingNotAllowedException::forDossier();
        }

        if (! $batch->getStatus()->isDownloadable()) {
            return $this->redirectToRoute('app_woodecision_batch_detail', [
                'documentPrefix' => $wooDecision->getDocumentPrefix(),
                'dossierNumber' => $wooDecision->getDossierNumber(),
                'batchId' => $batch->getId(),
            ]);
        }

        return $this->downloadHelper->getResponseForBatchDownload($batch);
    }

    #[Cache(public: true, maxage: 600, mustRevalidate: true)]
    #[Route(
        '/dossier/{documentPrefix}/{dossierNumber}/bijlage/{attachmentId}',
        name: 'app_woodecision_attachment_detail',
        methods: ['GET'],
    )]
    public function decisionAttachmentDetail(
        #[ValueResolver('dossierWithAccessCheck')] WooDecision $wooDecision,
        #[MapEntity(expr: 'repository.findForDossierByPrefixAndDossierNumber(documentPrefix, dossierNumber, attachmentId)')]
        WooDecisionAttachment $attachment,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem($this->getPublicationReason($wooDecision), 'app_woodecision_detail', [
            'documentPrefix' => $wooDecision->getDocumentPrefix(),
            'dossierNumber' => $wooDecision->getDossierNumber(),
        ]);
        $breadcrumbs->addItem('public.global.attachment');

        return $this->render('public/dossier/woo-decision/attachment.html.twig', [
            'dossier' => $this->wooDecisionViewFactory->make($wooDecision),
            'attachments' => $this->attachmentViewFactory->makeCollection($wooDecision),
            'attachment' => $this->attachmentViewFactory->make($wooDecision, $attachment),
            'file' => $this->dossierFileViewFactory->make(
                $wooDecision,
                $attachment,
                DossierFileType::ATTACHMENT,
            ),
        ]);
    }

    #[Cache(maxage: 600, public: true, mustRevalidate: true)]
    #[Route(
        '/dossier/{documentPrefix}/{dossierNumber}/document',
        name: 'app_woodecision_document_detail',
        methods: ['GET'],
    )]
    public function mainDocumentDetail(
        #[ValueResolver('dossierWithAccessCheck')] WooDecision $wooDecision,
        #[MapEntity(expr: 'repository.findForDossierByPrefixAndDossierNumber(documentPrefix, dossierNumber)')]
        WooDecisionMainDocument $mainDocument,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem($this->getPublicationReason($wooDecision), 'app_woodecision_detail', [
            'documentPrefix' => $wooDecision->getDocumentPrefix(),
            'dossierNumber' => $wooDecision->getDossierNumber(),
        ]);
        $breadcrumbs->addItem('public.global.main_document');

        return $this->render('public/dossier/woo-decision/document.html.twig', [
            'dossier' => $this->wooDecisionViewFactory->make($wooDecision),
            'attachments' => $this->attachmentViewFactory->makeCollection($wooDecision),
            'document' => $this->mainDocumentViewFactory->make($wooDecision, $mainDocument),
            'file' => $this->dossierFileViewFactory->make(
                $wooDecision,
                $mainDocument,
                DossierFileType::MAIN_DOCUMENT,
            ),
        ]);
    }

    private function getPublicationReason(WooDecision $dossier): string
    {
        $publicationReason = $dossier->getPublicationReason();
        Assert::isInstanceOf($publicationReason, PublicationReason::class);

        return $publicationReason->trans($this->translator);
    }
}
