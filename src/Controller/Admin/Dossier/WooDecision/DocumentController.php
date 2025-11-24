<?php

declare(strict_types=1);

namespace Shared\Controller\Admin\Dossier\WooDecision;

use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Service\DocumentWorkflow\DocumentWorkflowStatus;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

class DocumentController extends AbstractController
{
    #[Route(
        path: '/balie/dossier/woodecision/document/summary/{prefix}/{dossierId}/{documentId}',
        name: 'app_admin_dossier_woodecision_document',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('AuthMatrix.dossier.update', subject: 'dossier')]
    public function document(
        Breadcrumbs $breadcrumbs,
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])] WooDecision $dossier,
        #[MapEntity(expr: 'repository.findOneByDossierNrAndDocumentNr(prefix, dossierId,documentId)')] Document $document,
    ): Response {
        $breadcrumbs->addRouteItem(
            $dossier->getTitle() ?? '',
            'app_admin_dossier',
            ['prefix' => $dossier->getDocumentPrefix(), 'dossierId' => $dossier->getDossierNr()]
        );
        $breadcrumbs->addRouteItem(
            'admin.dossiers.woo-decision.step.documents',
            'app_admin_dossier_woodecision_documents_edit',
            ['prefix' => $dossier->getDocumentPrefix(), 'dossierId' => $dossier->getDossierNr()]
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
