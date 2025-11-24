<?php

declare(strict_types=1);

namespace Shared\Controller\Public\Dossier\WooDecision;

use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Shared\Domain\Publication\Dossier\FileProvider\DossierFileType;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\ViewModel\DocumentViewFactory;
use Shared\Domain\Publication\Dossier\Type\WooDecision\ViewModel\WooDecisionViewFactory;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\ViewModel\DossierFileViewFactory;
use Shared\Domain\Search\Index\Dossier\Mapper\PrefixedDossierNr;
use Shared\Service\Search\Model\FacetKey;
use Shared\Service\Security\DossierVoter;
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
class DocumentController extends AbstractController
{
    public function __construct(
        private readonly DocumentRepository $documentRepository,
        private readonly PaginatorInterface $paginator,
        private readonly WooDecisionViewFactory $wooDecisionViewFactory,
        private readonly DocumentViewFactory $documentViewFactory,
        private readonly DossierFileViewFactory $dossierFileViewFactory,
    ) {
    }

    #[Cache(maxage: 600, public: true, mustRevalidate: true)]
    #[Route('/dossier/{prefix}/{dossierId}/document/{documentId}', name: 'app_document_detail', methods: ['GET'])]
    public function detail(
        #[ValueResolver('dossierWithAccessCheck')] WooDecision $dossier,
        #[MapEntity(expr: 'repository.findOneByDossierNrAndDocumentNr(prefix, dossierId, documentId)')] Document $document,
        Breadcrumbs $breadcrumbs,
        Request $request,
    ): Response {
        $this->denyAccessUnlessGranted(DossierVoter::VIEW, $document);

        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem('global.decision', 'app_woodecision_detail', [
            'prefix' => $dossier->getDocumentPrefix(),
            'dossierId' => $dossier->getDossierNr(),
        ]);
        $breadcrumbs->addItem('global.document');

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

        return $this->render('public/dossier/woo-decision/document/details.html.twig', [
            'dossier' => $this->wooDecisionViewFactory->make($dossier),
            'document' => $this->documentViewFactory->make($document),
            'thread' => $threadDocPaginator,
            'family' => $familyDocPaginator,
            'file' => $this->dossierFileViewFactory->make(
                $dossier,
                $document,
                DossierFileType::DOCUMENT,
            ),
            'family_search_url' => $this->generateUrl(
                'app_search',
                [
                    FacetKey::PREFIXED_DOSSIER_NR->getParamName() => [PrefixedDossierNr::forDossier($dossier)],
                    FacetKey::FAMILY->getParamName() => [$document->getFamilyId()],
                ]
            ),
            'thread_search_url' => $this->generateUrl(
                'app_search',
                [
                    FacetKey::PREFIXED_DOSSIER_NR->getParamName() => [PrefixedDossierNr::forDossier($dossier)],
                    FacetKey::THREAD->getParamName() => [$document->getThreadId()],
                ]
            ),
            'referred_search_url' => $this->generateUrl(
                'app_search',
                [
                    FacetKey::PREFIXED_DOSSIER_NR->getParamName() => [PrefixedDossierNr::forDossier($dossier)],
                    FacetKey::REFERRED_DOCUMENT_NR->getParamName() => [$document->getDocumentNr()],
                ]
            ),
        ]);
    }
}
