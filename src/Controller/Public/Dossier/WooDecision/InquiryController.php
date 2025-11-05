<?php

declare(strict_types=1);

namespace App\Controller\Public\Dossier\WooDecision;

use App\Doctrine\DocumentConditions;
use App\Domain\Publication\BatchDownload\BatchDownloadScope;
use App\Domain\Publication\BatchDownload\OnDemandZipGenerator;
use App\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use App\Domain\Publication\Dossier\Type\WooDecision\Inquiry\InquiryRepository;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Search\Index\Dossier\Mapper\PrefixedDossierNr;
use App\Service\DownloadResponseHelper;
use App\Service\Inquiry\InquirySessionService;
use App\Service\Search\Model\FacetKey;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Annotation\Route;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

/**
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
class InquiryController extends AbstractController
{
    private const int MAX_DOCUMENTS_PER_PAGE = 10;
    private const int MAX_DOSSIERS_PER_PAGE = 10;

    public function __construct(
        private readonly InquirySessionService $inquirySession,
        private readonly PaginatorInterface $paginator,
        private readonly InquiryRepository $inquiryRepository,
        private readonly DownloadResponseHelper $downloadHelper,
        private readonly OnDemandZipGenerator $onDemandZipGenerator,
    ) {
    }

    #[Route('/zaak/{token}', name: 'app_inquiry_detail', methods: ['GET'])]
    public function detail(
        #[MapEntity(mapping: ['token' => 'token'])] Inquiry $inquiry,
    ): Response {
        $this->inquirySession->saveInquiry($inquiry);

        $documentCount = $this->inquiryRepository->countDocumentsByJudgement($inquiry);

        $searchUrl = $this->generateUrl(
            'app_search',
            [
                FacetKey::INQUIRY_DOCUMENTS->getParamName() => [$inquiry->getId()],
            ],
        );

        $query = $this->inquiryRepository->getDossiersForInquiryQueryBuilder($inquiry);
        $query->orderBy('dos.decisionDate', 'DESC')->setMaxResults(self::MAX_DOSSIERS_PER_PAGE);
        $dossiers = $this->paginator->paginate($query);

        return $this->render('public/dossier/woo-decision/inquiry/detail.html.twig', [
            'inquiry' => $inquiry,
            'dossiers' => $dossiers,
            'dossierCount' => $this->inquiryRepository->countPubliclyAvailableDossiers($inquiry),
            'scheduledDossiers' => $inquiry->getScheduledDossiers(),
            'searchUrl' => $searchUrl,
            'documentCount' => $documentCount,
            'maxDossiersPerPage' => self::MAX_DOSSIERS_PER_PAGE,
        ]);
    }

    #[Route('/zaak/{token}/inventarislijst/download', name: 'app_inquiry_inventory_download', methods: ['GET'])]
    public function downloadInventory(
        #[MapEntity(mapping: ['token' => 'token'])] Inquiry $inquiry,
    ): StreamedResponse {
        return $this->downloadHelper->getResponseForEntityWithFileInfo($inquiry->getInventory());
    }

    #[Route('/zaak/{token}/download/{prefix}/{dossierId}', name: 'app_inquiry_download_zip', methods: ['GET', 'POST'])]
    public function downloadZip(
        #[MapEntity(mapping: ['token' => 'token'])] Inquiry $inquiry,
        #[ValueResolver('dossierWithAccessCheck')] WooDecision $dossier,
    ): Response {
        $scope = BatchDownloadScope::forInquiryAndWooDecision($inquiry, $dossier);

        return $this->onDemandZipGenerator->getStreamedResponse($scope);
    }

    #[Route('/zaak/{token}/dossiers', name: 'app_inquiry_dossiers', methods: ['GET'])]
    public function dossiers(
        #[MapEntity(mapping: ['token' => 'token'])] Inquiry $inquiry,
        Request $request,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem(
            text: 'public.inquiry.page_title',
            route: 'app_inquiry_detail',
            parameters: ['token' => $inquiry->getToken()],
            translationParameters: ['case_number' => $inquiry->getCasenr()],
        );
        $breadcrumbs->addItem(
            text: 'public.inquiry.dossiers_breadcrumb',
        );

        $this->inquirySession->saveInquiry($inquiry);

        $searchUrl = $this->generateUrl(
            'app_search',
            [
                FacetKey::INQUIRY_DOCUMENTS->getParamName() => [$inquiry->getId()],
            ],
        );

        $dossiers = $this->paginator->paginate(
            $this->inquiryRepository->getDossiersForInquiryQueryBuilder($inquiry),
            $request->query->getInt('p', 1),
            20,
            ['pageParameterName' => 'p'],
        );

        return $this->render('public/dossier/woo-decision/inquiry/dossiers.html.twig', [
            'inquiry' => $inquiry,
            'dossiers' => $dossiers,
            'searchUrl' => $searchUrl,
            'maxDossiersPerPage' => self::MAX_DOSSIERS_PER_PAGE,
        ]);
    }

    #[Route('/zaak/{token}/dossier/{prefix}/{dossierId}', name: 'app_inquiry_dossier', methods: ['GET'])]
    public function dossier(
        #[MapEntity(mapping: ['token' => 'token'])] Inquiry $inquiry,
        #[ValueResolver('dossierWithAccessCheck')] WooDecision $dossier,
        Request $request,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem(
            text: 'public.inquiry.page_title',
            route: 'app_inquiry_detail',
            parameters: ['token' => $inquiry->getToken()],
            translationParameters: ['case_number' => $inquiry->getCasenr()],
        );
        $breadcrumbs->addItem(
            text: 'public.inquiry.dossier_detail_breadcrumb',
            translationParameters: ['title' => $dossier->getTitle()],
        );

        $this->inquirySession->saveInquiry($inquiry);

        $downloadUrl = $this->generateUrl(
            'app_inquiry_download_zip',
            [
                'token' => $inquiry->getToken(),
                'prefix' => $dossier->getDocumentPrefix(),
                'dossierId' => $dossier->getDossierNr(),
            ]
        );

        $searchUrl = $this->generateUrl(
            'app_search',
            [
                FacetKey::INQUIRY_DOCUMENTS->getParamName() => [$inquiry->getId()],
                FacetKey::PREFIXED_DOSSIER_NR->getParamName() => [PrefixedDossierNr::forDossier($dossier)],
            ],
        );

        $docQuery = $this->inquiryRepository->getDocsForInquiryDossierQueryBuilder($inquiry, $dossier);

        $publicPagination = $this->paginator->paginate(
            DocumentConditions::onlyPubliclyAvailable($docQuery),
            $request->query->getInt('pu', 1),
            self::MAX_DOCUMENTS_PER_PAGE,
            ['pageParameterName' => 'pu'],
        );

        $alreadyPublicPagination = $this->paginator->paginate(
            DocumentConditions::onlyAlreadyPublic($docQuery),
            $request->query->getInt('pa', 1),
            self::MAX_DOCUMENTS_PER_PAGE,
            ['pageParameterName' => 'pa'],
        );

        $notPublicPagination = $this->paginator->paginate(
            DocumentConditions::notPubliclyAvailable($docQuery),
            $request->query->getInt('pn', 1),
            self::MAX_DOCUMENTS_PER_PAGE,
            ['pageParameterName' => 'pn'],
        );

        $notOnlinePagination = $this->paginator->paginate(
            DocumentConditions::notOnline($docQuery),
            $request->query->getInt('po', 1),
            self::MAX_DOCUMENTS_PER_PAGE,
            ['pageParameterName' => 'po'],
        );

        return $this->render('public/dossier/woo-decision/inquiry/dossier.html.twig', [
            'inquiry' => $inquiry,
            'dossier' => $dossier,
            'public_docs' => $publicPagination,
            'already_public_docs' => $alreadyPublicPagination,
            'not_public_docs' => $notPublicPagination,
            'not_online_docs' => $notOnlinePagination,
            'searchUrl' => $searchUrl,
            'downloadUrl' => $downloadUrl,
        ]);
    }
}
