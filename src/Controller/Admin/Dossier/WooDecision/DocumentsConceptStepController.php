<?php

declare(strict_types=1);

namespace App\Controller\Admin\Dossier\WooDecision;

use App\Domain\Publication\Dossier\Step\StepActionHelper;
use App\Domain\Publication\Dossier\Step\StepName;
use App\Domain\Publication\Dossier\Type\WooDecision\ProductionReportDispatcher;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Form\Dossier\WooDecision\DocumentUploadType;
use App\Form\Dossier\WooDecision\InventoryType;
use App\ValueObject\InventoryStatus;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
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

        $inventoryForm = $this->createForm(InventoryType::class);
        $inventoryForm->handleRequest($request);
        if ($inventoryForm->isSubmitted() && $inventoryForm->isValid()) {
            $uploadedFile = $inventoryForm->get('inventory')->getData();
            if (! $uploadedFile instanceof UploadedFile) {
                throw new \RuntimeException('Missing inventory uploadfile');
            }

            $this->dispatcher->dispatchInitiateProductionReportUpdateCommand($dossier, $uploadedFile);
        }

        $processRun = $this->documentsHelper->mapProcessRunToForm($dossier, $inventoryForm);

        $documentForm = $this->createForm(DocumentUploadType::class, $dossier);

        $dataPath = null;
        if ($processRun?->isNotFinal()) {
            $dataPath = 'app_admin_dossier_woodecision_documents_concept_inventory_status';
        }

        return $this->render('admin/dossier/woo-decision/documents/concept.html.twig', [
            'dossier' => $dossier,
            'processRun' => $processRun,
            'workflowStatus' => $wizardStatus,
            'inventoryForm' => $inventoryForm,
            'documentForm' => $documentForm,
            'dataPath' => $dataPath,
            'inventoryStatus' => new InventoryStatus($dossier),
        ]);
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

        return $this->documentsHelper->getInventoryProcessResponse($dossier);
    }

    #[Route(
        path: '/balie/dossier/woodecision/documents/concept/inventory-delete/{prefix}/{dossierId}',
        name: 'app_admin_dossier_woodecision_documents_concept_inventory_delete',
        methods: ['GET'],
    )]
    #[IsGranted('AuthMatrix.dossier.create', subject: 'dossier')]
    public function inventoryDelete(
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])] WooDecision $dossier,
    ): Response {
        $wizardStatus = $this->stepHelper->getWizardStatus($dossier, StepName::DOCUMENTS);
        if (! $wizardStatus->isCurrentStepAccessibleInConceptMode()) {
            return $this->stepHelper->redirectToFirstOpenStep($wizardStatus);
        }

        $this->dispatcher->dispatchRemoveInventoryCommand($dossier);

        return $this->stepHelper->redirectToCurrentStep($wizardStatus);
    }
}
