<?php

declare(strict_types=1);

namespace Shared\Controller\Admin\Dossier\WooDecision;

use Huluti\BreadcrumbsBundle\Model\Breadcrumbs;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Service\DocumentWorkflow\DocumentWorkflowStatus;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DocumentController extends AbstractController
{
    #[Route(
        path: '/balie/dossier/woodecision/document/summary/{prefix}/{dossierNumber}/{documentNumber}',
        name: 'app_admin_dossier_woodecision_document',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('AuthMatrix.dossier.update', subject: 'dossier')]
    public function document(
        Breadcrumbs $breadcrumbs,
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierNumber' => 'dossierNumber'])] WooDecision $dossier,
        #[MapEntity(expr: 'repository.findOneByDossierNumberAndDocumentNumber(prefix, dossierNumber, documentNumber)')] Document $document,
    ): Response {
        $breadcrumbs->addRouteItem(
            (string) $dossier->getTitle(),
            'app_admin_dossier',
            ['prefix' => $dossier->getDocumentPrefix(), 'dossierNumber' => $dossier->getDossierNumber()],
        );
        $breadcrumbs->addRouteItem(
            'admin.dossiers.woo-decision.step.documents',
            'app_admin_dossier_woodecision_documents_edit',
            ['prefix' => $dossier->getDocumentPrefix(), 'dossierNumber' => $dossier->getDossierNumber()],
        );
        $breadcrumbs->addItem($document->getFileInfo()->getName() ?? '');

        return $this->render('admin/dossier/woo-decision/document/details.html.twig', [
            'dossier' => $dossier,
            'document' => $document,
            'breadcrumbs' => $breadcrumbs,
            'workflow' => new DocumentWorkflowStatus($document),
        ]);
    }
}
