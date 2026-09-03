<?php

declare(strict_types=1);

namespace Shared\Controller\Admin;

use Huluti\BreadcrumbsBundle\Model\Breadcrumbs;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Diagnostics for #7686: shows whether every document_number can be derived from its
 * publication_context and document_id. Temporary — remove once the generated column has shipped.
 */
class DocumentNumberCheckController extends AbstractController
{
    private const int ROW_LIMIT = 100;

    public function __construct(private readonly DocumentRepository $documentRepository)
    {
    }

    #[Route('/balie/data-checks/document-number', name: 'app_admin_document_number_check', methods: ['GET'])]
    #[IsGranted('AuthMatrix.data_check.read')]
    public function index(Breadcrumbs $breadcrumbs): Response
    {
        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem('global.admin', 'app_admin');
        $breadcrumbs->addRouteItem('admin.data_checks.manage', 'app_admin_data_checks');
        $breadcrumbs->addItem('Documentnummer controle');

        return $this->render('admin/document-number-check/index.html.twig', [
            'checks' => [
                [
                    'title' => 'Geen publicatiecontext',
                    'description' => 'De publicatiecontext is leeg.',
                    'total' => $this->documentRepository->countDocumentsWithoutPublicationContext(),
                    'rows' => $this->documentRepository->getDocumentsWithoutPublicationContext(self::ROW_LIMIT),
                ],
                [
                    'title' => 'Afwijkend documentnummer',
                    'description' => "Het opgeslagen documentnummer is niet gelijk aan publicatiecontext + '-' + document-ID.",
                    'total' => $this->documentRepository->countDocumentsWithDriftedDocumentNumber(),
                    'rows' => $this->documentRepository->getDocumentsWithDriftedDocumentNumber(self::ROW_LIMIT),
                ],
                [
                    'title' => 'Hoofdletters in document-ID',
                    'description' => 'Het document-ID bevat hoofdletters en wordt bij de eerstvolgende opslag hernummerd.',
                    'total' => $this->documentRepository->countDocumentsWithMixedCaseDocumentId(),
                    'rows' => $this->documentRepository->getDocumentsWithMixedCaseDocumentId(self::ROW_LIMIT),
                ],
            ],
            'row_limit' => self::ROW_LIMIT,
        ]);
    }
}
