<?php

declare(strict_types=1);

namespace Shared\Controller\Admin\Dossier\WooDecision;

use Huluti\BreadcrumbsBundle\Model\Breadcrumbs;
use Shared\Domain\Publication\Dossier\Step\StepActionHelper;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Service\DocumentWorkflow\DocumentWorkflowStatus;
use Shared\ValueObject\DocumentNumber;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DocumentController extends AbstractController
{
    public const string ROUTE_NAME_DOSSIER_WOODECISION_DOCUMENT = 'app_admin_dossier_woodecision_document';

    public function __construct(
        private readonly DocumentRepository $documentRepository,
        private readonly StepActionHelper $stepActionHelper,
    ) {
    }

    #[Route(
        '/balie/dossier/woodecision/document/summary/{documentPrefix}/{dossierNumber}/{documentNumber}',
        self::ROUTE_NAME_DOSSIER_WOODECISION_DOCUMENT,
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('AuthMatrix.dossier.update', subject: 'dossier')]
    public function document(
        Breadcrumbs $breadcrumbs,
        #[MapEntity(mapping: ['documentPrefix' => 'documentPrefix', 'dossierNumber' => 'dossierNumber'])] WooDecision $dossier,
        DocumentNumber $documentNumber,
    ): Response {
        $document = $this->documentRepository->findOneByDossierNumberAndDocumentNumber(
            $dossier->getDocumentPrefix(),
            $dossier->getDossierNumber(),
            $documentNumber,
        );

        if ($document === null) {
            throw $this->createNotFoundException();
        }

        $this->stepActionHelper->addDossierToBreadcrumbs($breadcrumbs, $dossier);
        $breadcrumbs->addRouteItem(
            'admin.dossiers.woo-decision.step.documents',
            'app_admin_dossier_woodecision_documents_edit',
            ['documentPrefix' => $dossier->getDocumentPrefix(), 'dossierNumber' => $dossier->getDossierNumber()],
        );
        $breadcrumbs->addItem('global.document');

        return $this->render('admin/dossier/woo-decision/document/details.html.twig', [
            'dossier' => $dossier,
            'document' => $document,
            'breadcrumbs' => $breadcrumbs,
            'workflow' => new DocumentWorkflowStatus($document),
        ]);
    }
}
