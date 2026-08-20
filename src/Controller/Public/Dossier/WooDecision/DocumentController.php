<?php

declare(strict_types=1);

namespace Shared\Controller\Public\Dossier\WooDecision;

use Huluti\BreadcrumbsBundle\Model\Breadcrumbs;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Shared\Domain\Publication\Dossier\FileProvider\DossierFileType;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\ViewModel\DocumentViewFactory;
use Shared\Domain\Publication\Dossier\Type\WooDecision\PublicationReason;
use Shared\Domain\Publication\Dossier\Type\WooDecision\ViewModel\WooDecisionViewFactory;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\ViewModel\DossierFileViewFactory;
use Shared\Domain\Search\Index\Dossier\Mapper\PrefixedDossierNumber;
use Shared\Service\Search\Model\FacetKey;
use Shared\Service\Security\DossierVoter;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Webmozart\Assert\Assert;

class DocumentController extends AbstractController
{
    public function __construct(
        private readonly DocumentRepository $documentRepository,
        private readonly PaginatorInterface $paginator,
        private readonly WooDecisionViewFactory $wooDecisionViewFactory,
        private readonly DocumentViewFactory $documentViewFactory,
        private readonly DossierFileViewFactory $dossierFileViewFactory,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Cache(maxage: 600, public: true, mustRevalidate: true)]
    #[Route('/dossier/{documentPrefix}/{dossierNumber}/document/{documentNumber}', name: 'app_document_detail', methods: ['GET'])]
    public function detail(
        #[ValueResolver('dossierWithAccessCheck')] WooDecision $wooDecision,
        #[MapEntity(expr: 'repository.findOneByDossierNumberAndDocumentNumber(documentPrefix, dossierNumber, documentNumber)')] Document $document,
        Breadcrumbs $breadcrumbs,
        Request $request,
    ): Response {
        $this->denyAccessUnlessGranted(DossierVoter::VIEW, $document);

        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem($this->getPublicationReason($wooDecision), 'app_woodecision_detail', [
            'documentPrefix' => $wooDecision->getDocumentPrefix(),
            'dossierNumber' => $wooDecision->getDossierNumber(),
        ]);
        $breadcrumbs->addItem('global.document');

        /** @var PaginationInterface<array-key,Document> $threadDocPaginator */
        $threadDocPaginator = $this->paginator->paginate(
            $this->documentRepository->getRelatedDocumentsByThread($wooDecision, $document),
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
            $this->documentRepository->getRelatedDocumentsByFamily($wooDecision, $document),
            $request->query->getInt('fp', 1),
            100,
            [
                'pageParameterName' => 'fp',
                'sortFieldParameterName' => 'fs',
                'sortDirectionParameterName' => 'fsd',
            ],
        );

        return $this->render('public/dossier/woo-decision/document/details.html.twig', [
            'dossier' => $this->wooDecisionViewFactory->make($wooDecision),
            'document' => $this->documentViewFactory->make($document),
            'thread' => $threadDocPaginator,
            'family' => $familyDocPaginator,
            'file' => $this->dossierFileViewFactory->make(
                $wooDecision,
                $document,
                DossierFileType::DOCUMENT,
            ),
            'family_search_url' => $this->generateUrl(
                'app_search',
                [
                    FacetKey::PREFIXED_DOSSIER_NUMBER->getParamName() => [PrefixedDossierNumber::forDossier($wooDecision)],
                    FacetKey::FAMILY->getParamName() => [$document->getFamilyId()],
                ],
            ),
            'thread_search_url' => $this->generateUrl(
                'app_search',
                [
                    FacetKey::PREFIXED_DOSSIER_NUMBER->getParamName() => [PrefixedDossierNumber::forDossier($wooDecision)],
                    FacetKey::THREAD->getParamName() => [$document->getThreadId()],
                ],
            ),
            'referred_search_url' => $this->generateUrl(
                'app_search',
                [
                    FacetKey::PREFIXED_DOSSIER_NUMBER->getParamName() => [PrefixedDossierNumber::forDossier($wooDecision)],
                    FacetKey::REFERRED_DOCUMENT_NUMBER->getParamName() => [$document->getDocumentNumber()],
                ],
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
