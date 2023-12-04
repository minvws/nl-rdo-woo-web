<?php

declare(strict_types=1);

namespace App\Controller;

use App\Doctrine\DocumentConditions;
use App\Entity\BatchDownload;
use App\Entity\Inquiry;
use App\Form\Inquiry\InquiryFilterFormType;
use App\Repository\DossierRepository;
use App\Repository\InquiryRepository;
use App\Service\DossierService;
use App\Service\DownloadResponseHelper;
use App\Service\Inquiry\InquiryService;
use App\Service\Inquiry\InquirySessionService;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Routing\Annotation\Route;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class InquiryController extends AbstractController
{
    protected const MAX_ITEMS_PER_PAGE = 100;

    public function __construct(
        protected Security $security,
        protected InquirySessionService $inquirySession,
        private readonly PaginatorInterface $paginator,
        private readonly DossierRepository $dossierRepository,
        private readonly DossierService $dossierService,
        private readonly InquiryRepository $inquiryRepository,
        private readonly DownloadResponseHelper $downloadHelper,
        private readonly InquiryService $inquiryService,
    ) {
    }

    #[Route('/inquiry/{token}', name: 'app_inquiry_detail', methods: ['GET'])]
    public function detail(Inquiry $inquiry, Request $request): Response
    {
        $this->inquirySession->saveInquiry($inquiry);

        $documentCount = $this->inquiryRepository->countDocumentsByJudgement($inquiry);

        $form = $this->createForm(InquiryFilterFormType::class, $inquiry, ['filterParam' => $request->query->getString('filter')]);
        $form->handleRequest($request);

        $filter = $form->isSubmitted() && $form->isValid() ? $form->get('filter')->getData() : InquiryFilterFormType::CASE;
        if ($filter === InquiryFilterFormType::CASE) {
            $docQuery = $this->inquiryRepository->getDocumentsForPubliclyAvailableDossiers($inquiry);
            $searchUrlParams = [
                'dci' => [$inquiry->getId()],
            ];
        } else {
            $dossier = $this->dossierRepository->findOneBy(['dossierNr' => $filter]);
            if (! $dossier || ! $this->dossierService->isViewingAllowed($dossier)) {
                throw $this->createNotFoundException('Dossier not found');
            }
            $docQuery = $this->inquiryRepository->getDocsForInquiryDossierQueryBuilder($inquiry, $dossier);
            $searchUrlParams = [
                'dci' => [$inquiry->getId()],
                'dnr' => [$dossier->getDossierNr()],
            ];
        }

        $downloadUrl = $this->generateUrl(
            'app_inquiry_batch',
            [
                'token' => $inquiry->getToken(),
                'filter' => $filter,
            ]
        );

        $searchUrl = $this->generateUrl('app_search', $searchUrlParams);

        $publicPagination = $this->paginator->paginate(
            DocumentConditions::onlyPubliclyAvailable($docQuery),
            $request->query->getInt('pu', 1),
            self::MAX_ITEMS_PER_PAGE,
            ['pageParameterName' => 'pu'],
        );

        $notPublicPagination = $this->paginator->paginate(
            DocumentConditions::notPubliclyAvailable($docQuery),
            $request->query->getInt('pn', 1),
            self::MAX_ITEMS_PER_PAGE,
            ['pageParameterName' => 'pn'],
        );

        $notOnlinePagination = $this->paginator->paginate(
            DocumentConditions::notOnline($docQuery),
            $request->query->getInt('po', 1),
            self::MAX_ITEMS_PER_PAGE,
            ['pageParameterName' => 'po'],
        );

        return $this->render('inquiry/index.html.twig', [
            'inquiry' => $inquiry,
            'dossiers' => $inquiry->getPubliclyAvailableDossiers(),
            'scheduledDossiers' => $inquiry->getScheduledDossiers(),
            'public_docs' => $publicPagination,
            'not_public_docs' => $notPublicPagination,
            'not_online_docs' => $notOnlinePagination,
            'form' => $form,
            'searchUrl' => $searchUrl,
            'downloadUrl' => $downloadUrl,
            'documentCount' => $documentCount,
        ]);
    }

    #[Route('/inquiry/{token}/inventory/download', name: 'app_inquiry_inventory_download', methods: ['GET'])]
    public function downloadInventory(Inquiry $inquiry): StreamedResponse
    {
        return $this->downloadHelper->getResponseForEntityWithFileInfo($inquiry->getInventory());
    }

    #[Route('/inquiry/{token}/batch/{filter}', name: 'app_inquiry_batch', methods: ['POST'])]
    public function createBatch(Inquiry $inquiry, string $filter): Response
    {
        if ($filter === InquiryFilterFormType::CASE) {
            $docQuery = $this->inquiryRepository->getDocumentsForPubliclyAvailableDossiers($inquiry);
        } else {
            $dossier = $this->dossierRepository->findOneBy(['dossierNr' => $filter]);
            if (! $dossier || ! $this->dossierService->isViewingAllowed($dossier)) {
                throw $this->createNotFoundException('Dossier not found');
            }
            $docQuery = $this->inquiryRepository->getDocsForInquiryDossierQueryBuilder($inquiry, $dossier);
        }

        $batch = $this->inquiryService->generateBatch($inquiry, $docQuery);
        if (! $batch) {
            throw $this->createNotFoundException('Inquiry batch not available');
        }

        return $this->redirectToRoute('app_inquiry_batch_detail', [
            'token' => $inquiry->getToken(),
            'batchId' => $batch->getId(),
        ]);
    }

    #[Route('/inquiry/{token}/batch/detail/{batchId}', name: 'app_inquiry_batch_detail', methods: ['GET'])]
    public function batch(
        Inquiry $inquiry,
        #[MapEntity(mapping: ['batchId' => 'id'])] BatchDownload $batch,
        Breadcrumbs $breadcrumbs
    ): Response {
        if ($batch->getEntity() !== $inquiry) {
            throw $this->createNotFoundException('Inquiry download not found');
        }

        $breadcrumbs->addRouteItem('Home', 'app_home');
        $breadcrumbs->addRouteItem($inquiry->getCasenr(), 'app_inquiry_detail', ['token' => $inquiry->getToken()]);
        $breadcrumbs->addItem('Download');

        return $this->render('batchdownload/batch.html.twig', [
            'inquiry' => $inquiry,
            'batch' => $batch,
            'page_title' => 'Download inquiry archive',
            'download_path' => $this->generateUrl('app_inquiry_batch_download', ['token' => $inquiry->getToken(), 'batchId' => $batch->getId()]),
        ]);
    }

    #[Cache(public: true, maxage: 172800, mustRevalidate: true)]
    #[Route('/inquiry/{token}/batch/{batchId}/download', name: 'app_inquiry_batch_download', methods: ['GET'])]
    public function batchDownload(
        Inquiry $inquiry,
        #[MapEntity(mapping: ['batchId' => 'id'])] BatchDownload $batch,
    ): Response {
        if ($batch->getEntity() !== $inquiry) {
            throw $this->createNotFoundException('Inquiry download not found');
        }

        if ($batch->getStatus() !== BatchDownload::STATUS_COMPLETED) {
            return $this->redirectToRoute('app_inquiry_batch_detail', [
                'token' => $inquiry->getToken(),
                'batchId' => $batch->getId(),
            ]);
        }

        return $this->downloadHelper->getResponseForBatchDownload($batch);
    }
}
