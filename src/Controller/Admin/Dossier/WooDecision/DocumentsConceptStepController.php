<?php

declare(strict_types=1);

namespace Shared\Controller\Admin\Dossier\WooDecision;

use Shared\Domain\Publication\Dossier\Step\StepActionHelper;
use Shared\Domain\Publication\Dossier\Step\StepName;
use Shared\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReportDispatcher;
use Shared\Domain\Publication\Dossier\Type\WooDecision\ViewModel\ProductionReportStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Form\Dossier\WooDecision\InventoryType;
use Shared\Service\DossierWizard\DossierWizardStatus;
use Shared\Service\Uploader\UploadGroupId;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
class DocumentsConceptStepController extends AbstractController
{
    public function __construct(
        private readonly StepActionHelper $stepHelper,
        private readonly DocumentsStepHelper $documentsHelper,
        private readonly ProductionReportDispatcher $dispatcher,
    ) {
    }

    #[Route(
        path: '/balie/dossier/woodecision/documents/concept/{prefix}/{dossierId}',
        name: 'app_admin_dossier_woodecision_documents_concept',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('AuthMatrix.dossier.create', subject: 'dossier')]
    public function concept(
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])] WooDecision $dossier,
        Request $request,
    ): Response {
        $wizardStatus = $this->stepHelper->getWizardStatus($dossier, StepName::DOCUMENTS);
        if (! $wizardStatus->isCurrentStepAccessibleInConceptMode()) {
            return $this->stepHelper->redirectToFirstOpenStep($wizardStatus);
        }

        if ($request->query->has('confirm')) {
            $this->dispatcher->dispatchConfirmProductionReportUpdateCommand($dossier);
        }

        if ($request->query->has('reject')) {
            return $this->rejectProductionReport($dossier);
        }

        $inventoryForm = $this->createForm(InventoryType::class, $dossier);
        $inventoryForm->handleRequest($request);

        if ($this->stepHelper->isFormCancelled($inventoryForm)) {
            return $this->rejectProductionReport($dossier);
        }

        $formSubmitResponse = $this->processFormSubmit($dossier, $inventoryForm, $wizardStatus);
        if ($formSubmitResponse !== null) {
            return $formSubmitResponse;
        }

        $processRun = $this->documentsHelper->mapProcessRunToForm($dossier, $inventoryForm);

        $dataPath = null;
        if ($processRun?->isNotFinal()) {
            $dataPath = 'app_admin_dossier_woodecision_documents_concept_inventory_status';
        }

        return $this->render('admin/dossier/woo-decision/documents/concept.html.twig', [
            'dossier' => $dossier,
            'processRun' => $processRun,
            'workflowStatus' => $wizardStatus,
            'inventoryForm' => $inventoryForm,
            'dataPath' => $dataPath,
            'inventoryStatus' => new ProductionReportStatus($dossier),
            'uploadGroupId' => UploadGroupId::WOO_DECISION_DOCUMENTS,
        ]);
    }

    private function processFormSubmit(WooDecision $dossier, FormInterface $inventoryForm, DossierWizardStatus $wizardStatus): ?Response
    {
        if (! ($inventoryForm->isSubmitted() && $inventoryForm->isValid())) {
            return null;
        }

        $uploadedFile = $inventoryForm->get('inventory')->getData();

        if ($uploadedFile instanceof UploadedFile) {
            $this->dispatcher->dispatchInitiateProductionReportUpdateCommand($dossier, $uploadedFile);

            return null;
        } elseif ($dossier->isInventoryRequired()) {
            throw new \RuntimeException('Missing inventory uploadfile');
        } else {
            return $this->stepHelper->redirectToNextStep($wizardStatus);
        }
    }

    private function rejectProductionReport(WooDecision $dossier): Response
    {
        $this->dispatcher->dispatchRejectProductionReportUpdateCommand($dossier);

        return $this->redirectToRoute(
            'app_admin_dossier_woodecision_documents_concept',
            ['prefix' => $dossier->getDocumentPrefix(), 'dossierId' => $dossier->getDossierNr()]
        );
    }

    #[Route(
        path: '/balie/dossier/woodecision/documents/concept/inventory-status/{prefix}/{dossierId}',
        name: 'app_admin_dossier_woodecision_documents_concept_inventory_status',
        methods: ['GET'],
    )]
    #[IsGranted('AuthMatrix.dossier.create', subject: 'dossier')]
    public function inventoryProcessStatus(
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])] WooDecision $dossier,
    ): Response {
        $wizardStatus = $this->stepHelper->getWizardStatus($dossier, StepName::DOCUMENTS);
        if (! $wizardStatus->isCurrentStepAccessibleInConceptMode()) {
            return $this->stepHelper->redirectToFirstOpenStep($wizardStatus);
        }

        return $this->documentsHelper->getProductionReportProcessResponse($dossier);
    }
}
