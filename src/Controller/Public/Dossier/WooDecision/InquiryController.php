<?php

declare(strict_types=1);

namespace App\Controller\Public\Dossier\WooDecision;

use App\Doctrine\DocumentConditions;
use App\Domain\Publication\BatchDownload\BatchDownloadScope;
use App\Domain\Publication\BatchDownload\OnDemandZipGenerator;
use App\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use App\Domain\Publication\Dossier\Type\WooDecision\Inquiry\InquiryRepository;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use App\Domain\Search\Index\Dossier\Mapper\PrefixedDossierNr;
use App\Exception\ViewingNotAllowedException;
use App\Form\Inquiry\InquiryFilterFormType;
use App\Service\DownloadResponseHelper;
use App\Service\Inquiry\InquirySessionService;
use App\Service\Search\Model\FacetKey;
use App\Service\Security\DossierVoter;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

/**
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
class InquiryController extends AbstractController
{
    protected const MAX_ITEMS_PER_PAGE = 100;

    public function __construct(
        protected Security $security,
        protected InquirySessionService $inquirySession,
        private readonly PaginatorInterface $paginator,
        private readonly WooDecisionRepository $wooDecisionRepository,
        private readonly InquiryRepository $inquiryRepository,
        private readonly DownloadResponseHelper $downloadHelper,
        private readonly OnDemandZipGenerator $onDemandZipGenerator,
    ) {
    }

    #[Route('/zaak/{token}', name: 'app_inquiry_detail', methods: ['GET'])]
    public function detail(
        #[MapEntity(mapping: ['token' => 'token'])] Inquiry $inquiry,
        Request $request,
    ): Response {
        $this->inquirySession->saveInquiry($inquiry);

        $documentCount = $this->inquiryRepository->countDocumentsByJudgement($inquiry);

        $form = $this->createForm(InquiryFilterFormType::class, $inquiry, ['filterParam' => $request->query->getString('filter')]);
        $form->handleRequest($request);

        $caseSearchParams = [
            FacetKey::INQUIRY_DOCUMENTS->getParamName() => [$inquiry->getId()],
        ];

        $filter = $form->isSubmitted() && $form->isValid() ? $form->get('filter')->getData() : InquiryFilterFormType::CASE;
        if ($filter === InquiryFilterFormType::CASE) {
            $docQuery = $this->inquiryRepository->getDocumentsForPubliclyAvailableDossiers($inquiry);
            $searchUrlParams = $caseSearchParams;
        } else {
            $dossier = $this->wooDecisionRepository->findOneBy(['dossierNr' => $filter]);
            if (! $dossier || ! $this->isGranted(DossierVoter::VIEW, $dossier)) {
                throw ViewingNotAllowedException::forDossier();
            }
            $docQuery = $this->inquiryRepository->getDocsForInquiryDossierQueryBuilder($inquiry, $dossier);
            $searchUrlParams = [
                FacetKey::INQUIRY_DOCUMENTS->getParamName() => [$inquiry->getId()],
                FacetKey::PREFIXED_DOSSIER_NR->getParamName() => [PrefixedDossierNr::forDossier($dossier)],
            ];
        }

        $downloadUrl = $this->generateUrl(
            'app_inquiry_download_zip_details',
            [
                'token' => $inquiry->getToken(),
                'filter' => $filter,
            ]
        );

        $searchUrl = $this->generateUrl('app_search', $searchUrlParams);
        $searchAllUrl = $this->generateUrl('app_search', $caseSearchParams);

        $publicPagination = $this->paginator->paginate(
            DocumentConditions::onlyPubliclyAvailable($docQuery),
            $request->query->getInt('pu', 1),
            self::MAX_ITEMS_PER_PAGE,
            ['pageParameterName' => 'pu'],
        );

        $alreadyPublicPagination = $this->paginator->paginate(
            DocumentConditions::onlyAlreadyPublic($docQuery),
            $request->query->getInt('pa', 1),
            self::MAX_ITEMS_PER_PAGE,
            ['pageParameterName' => 'pa'],
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

        return $this->render('public/dossier/woo-decision/inquiry/index.html.twig', [
            'inquiry' => $inquiry,
            'dossiers' => $inquiry->getPubliclyAvailableDossiers(),
            'scheduledDossiers' => $inquiry->getScheduledDossiers(),
            'public_docs' => $publicPagination,
            'already_public_docs' => $alreadyPublicPagination,
            'not_public_docs' => $notPublicPagination,
            'not_online_docs' => $notOnlinePagination,
            'form' => $form,
            'searchUrl' => $searchUrl,
            'searchAllUrl' => $searchAllUrl,
            'downloadUrl' => $downloadUrl,
            'documentCount' => $documentCount,
        ]);
    }

    #[Route('/zaak/{token}/inventarislijst/download', name: 'app_inquiry_inventory_download', methods: ['GET'])]
    public function downloadInventory(
        #[MapEntity(mapping: ['token' => 'token'])] Inquiry $inquiry,
    ): StreamedResponse {
        return $this->downloadHelper->getResponseForEntityWithFileInfo($inquiry->getInventory());
    }

    #[Route('/zaak/{token}/download/{filter}/details', name: 'app_inquiry_download_zip_details', methods: ['GET'])]
    public function batch(
        #[MapEntity(mapping: ['token' => 'token'])] Inquiry $inquiry,
        string $filter,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem($inquiry->getCasenr(), 'app_inquiry_detail', ['token' => $inquiry->getToken()]);
        $breadcrumbs->addItem('public.global.download');

        return $this->render('public/dossier/woo-decision/shared/streaming-download.html.twig', [
            'inquiry' => $inquiry,
            'download' => $this->onDemandZipGenerator->getDetails(BatchDownloadScope::forInquiry($inquiry)),
            'download_path' => $this->generateUrl('app_inquiry_download_zip', ['token' => $inquiry->getToken(), 'filter' => $filter]),
        ]);
    }

    #[Route('/zaak/{token}/download/{filter}', name: 'app_inquiry_download_zip', methods: ['GET'])]
    public function downloadZip(
        #[MapEntity(mapping: ['token' => 'token'])] Inquiry $inquiry,
        string $filter,
    ): Response {
        unset($filter);

        return $this->onDemandZipGenerator->getStreamedResponse(
            BatchDownloadScope::forInquiry($inquiry),
        );
    }
}
