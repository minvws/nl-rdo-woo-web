<?php

declare(strict_types=1);

namespace Shared\Controller\Admin\Dossier\WooDecision;

use Shared\Domain\Publication\Dossier\Step\StepActionHelper;
use Shared\Domain\Publication\Dossier\Step\StepName;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReportDispatcher;
use Shared\Domain\Publication\Dossier\Type\WooDecision\ViewModel\ProductionReportStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Form\Dossier\WooDecision\InventoryType;
use Shared\Service\Uploader\UploadGroupId;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

/**
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
class DocumentsEditStepController extends AbstractController
{
    public function __construct(
        private readonly StepActionHelper $stepHelper,
        private readonly DocumentRepository $documentRepository,
        private readonly DocumentsStepHelper $documentsHelper,
        private readonly ProductionReportDispatcher $dispatcher,
    ) {
    }

    #[Route(
        path: '/balie/dossier/woodecision/documents/edit/{prefix}/{dossierId}',
        name: 'app_admin_dossier_woodecision_documents_edit',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('AuthMatrix.document.update', subject: 'dossier')]
    public function edit(
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])] WooDecision $dossier,
        Request $request,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $wizardStatus = $this->stepHelper->getWizardStatus($dossier, StepName::DOCUMENTS);
        if (! $wizardStatus->isCurrentStepAccessibleInEditMode()) {
            return $this->stepHelper->redirectToDossier($dossier);
        }

        $query = $this->documentRepository->getDossierDocumentsForPaginationQuery($dossier);

        $pagination = $this->stepHelper->getPaginator(
            $query,
            $request->query->getInt('page', 1),
        );

        $this->stepHelper->addDossierToBreadcrumbs($breadcrumbs, $dossier, 'admin.dossiers.woo-decision.step.documents');

        $dataPath = null;
        if ($dossier->getProcessRun()?->isNotFinal()) {
            $dataPath = 'app_admin_dossier_woodecision_documents_edit_inventory_status';
        }

        return $this->render('admin/dossier/woo-decision/documents/edit.html.twig', [
            'breadcrumbs' => $breadcrumbs,
            'dossier' => $dossier,
            'workflowStatus' => $wizardStatus,
            'pagination' => $pagination,
            'dataPath' => $dataPath,
            'uploadGroupId' => UploadGroupId::WOO_DECISION_DOCUMENTS,
        ]);
    }

    #[Route(
        path: '/balie/dossier/woodecision/documents/edit/inventory-status/{prefix}/{dossierId}',
        name: 'app_admin_dossier_woodecision_documents_edit_inventory_status',
        methods: ['GET'],
    )]
    #[IsGranted('AuthMatrix.dossier.update', subject: 'dossier')]
    public function inventoryProcess(
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])] WooDecision $dossier,
    ): Response {
        $wizardStatus = $this->stepHelper->getWizardStatus($dossier, StepName::DOCUMENTS);
        if (! $wizardStatus->isCurrentStepAccessibleInEditMode()) {
            throw $this->createAccessDeniedException();
        }

        return $this->documentsHelper->getProductionReportProcessResponse($dossier);
    }

    #[Route(
        path: '/balie/dossier/woodecision/documents/edit/replace-inventory/{prefix}/{dossierId}',
        name: 'app_admin_dossier_woodecision_documents_edit_replace_inventory',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('AuthMatrix.dossier.update', subject: 'dossier')]
    public function replaceInventory(
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])] WooDecision $dossier,
        Request $request,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $wizardStatus = $this->stepHelper->getWizardStatus($dossier, StepName::DOCUMENTS);
        if (! $wizardStatus->isCurrentStepAccessibleInEditMode()) {
            return $this->stepHelper->redirectToDossier($dossier);
        }

        $form = $this->createForm(InventoryType::class, $dossier);
        $form->handleRequest($request);

        if ($this->stepHelper->isFormCancelled($form)) {
            return $this->redirectToRoute(
                'app_admin_dossier_woodecision_documents_edit',
                ['prefix' => $dossier->getDocumentPrefix(), 'dossierId' => $dossier->getDossierNr()]
            );
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $uploadedFile = $form->get('inventory')->getData();
            if (! $uploadedFile instanceof UploadedFile) {
                throw new \RuntimeException('Missing inventory uploadfile');
            }

            $this->dispatcher->dispatchInitiateProductionReportUpdateCommand($dossier, $uploadedFile);
        }

        if (intval($request->get('confirm')) === 1) {
            $this->dispatcher->dispatchConfirmProductionReportUpdateCommand($dossier);
        }

        if (intval($request->get('reject')) === 1) {
            $this->dispatcher->dispatchRejectProductionReportUpdateCommand($dossier);

            return $this->redirectToRoute(
                'app_admin_dossier_woodecision_documents_edit',
                ['prefix' => $dossier->getDocumentPrefix(), 'dossierId' => $dossier->getDossierNr()]
            );
        }

        $processRun = $this->documentsHelper->mapProcessRunToForm($dossier, $form);

        $dataPath = null;
        if ($processRun?->isNotFinal()) {
            $dataPath = 'app_admin_dossier_woodecision_documents_edit_inventory_status';
        }

        $this->stepHelper->addDossierToBreadcrumbs($breadcrumbs, $dossier);
        $breadcrumbs->addRouteItem(
            'admin.dossiers.woo-decision.step.documents',
            'app_admin_dossier_woodecision_documents_edit',
            ['prefix' => $dossier->getDocumentPrefix(), 'dossierId' => $dossier->getDossierNr()]
        );
        $breadcrumbs->addItem('admin.dossiers.woo-decision.step.replace_inventory');

        return $this->render('admin/dossier/woo-decision/documents/replace-inventory.html.twig', [
            'breadcrumbs' => $breadcrumbs,
            'dossier' => $dossier,
            'processRun' => $processRun,
            'workflowStatus' => $wizardStatus,
            'inventoryForm' => $form,
            'inventoryStatus' => new ProductionReportStatus($dossier),
            'dataPath' => $dataPath,
        ]);
    }
}
