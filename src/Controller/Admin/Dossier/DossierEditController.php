<?php

declare(strict_types=1);

namespace App\Controller\Admin\Dossier;

use App\Attribute\AuthMatrix;
use App\Entity\Dossier;
use App\Entity\WithdrawReason;
use App\Form\Document\WithdrawFormType;
use App\Form\Dossier\DecisionType;
use App\Form\Dossier\DeleteFormType;
use App\Form\Dossier\DetailsType;
use App\Form\Dossier\InventoryType;
use App\Form\Dossier\PublishType;
use App\Form\Dossier\TranslatableFormErrorMapper;
use App\Repository\DocumentRepository;
use App\Service\DossierWorkflow\DossierWorkflow;
use App\Service\DossierWorkflow\StepName;
use App\Service\Security\Authorization\AuthorizationMatrix;
use App\ValueObject\InventoryStatus;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DossierEditController extends AbstractController
{
    use DossierAuthorizationTrait;

    public function __construct(
        private readonly DossierWorkflow $workflow,
        private readonly DocumentRepository $documentRepository,
        private readonly PaginatorInterface $paginator,
        private readonly AuthorizationMatrix $authorizationMatrix,
        private readonly TranslatableFormErrorMapper $formErrorMapper,
    ) {
    }

    #[Route('/balie/dossier/{dossierId}/edit/details', name: 'app_admin_dossier_edit_details', methods: ['GET', 'POST'])]
    #[AuthMatrix('dossier.update')]
    public function details(
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier,
        Request $request,
        Breadcrumbs $breadcrumbs
    ): Response {
        $this->testIfDossierIsAllowedByUser($dossier);

        $workflowStatus = $this->workflow->getStatus($dossier, StepName::DETAILS);
        $currentStep = $workflowStatus->getCurrentStep();

        if ($workflowStatus->isConcept()) {
            return $this->redirectToRoute($currentStep->getConceptRouteName(), ['dossierId' => $dossier->getDossierNr()]);
        }

        $form = $this->createForm(DetailsType::class, $dossier);
        $form->handleRequest($request);

        // When the cancel button is clicked, redirect back to the dossier
        /** @var SubmitButton $cancelButton */
        $cancelButton = $form->get('cancel');
        if ($cancelButton->isClicked()) {
            return $this->redirectToRoute('app_admin_dossier', ['dossierId' => $dossier->getDossierNr()]);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $this->workflow->updateDetails($dossier);

            return $this->redirectToRoute('app_admin_dossier', ['dossierId' => $dossier->getDossierNr()]);
        }

        $breadcrumbs->addRouteItem($dossier->getTitle() ?? '', 'app_admin_dossier', ['dossierId' => $dossier->getDossierNr()]);
        $breadcrumbs->addItem('workflow_step_details');

        return $this->render('admin/dossier/edit/details.html.twig', [
            'breadcrumbs' => $breadcrumbs,
            'dossier' => $dossier,
            'workflowStatus' => $workflowStatus,
            'form' => $form,
        ]);
    }

    #[Route('/balie/dossier/{dossierId}/edit/decision', name: 'app_admin_dossier_edit_decision', methods: ['GET', 'POST'])]
    #[AuthMatrix('dossier.update')]
    public function decision(
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier,
        Request $request,
        Breadcrumbs $breadcrumbs
    ): Response {
        $this->testIfDossierIsAllowedByUser($dossier);

        $workflowStatus = $this->workflow->getStatus($dossier, StepName::DECISION);
        $currentStep = $workflowStatus->getCurrentStep();

        if ($workflowStatus->isConcept()) {
            return $this->redirectToRoute($currentStep->getConceptRouteName(), ['dossierId' => $dossier->getDossierNr()]);
        }

        $form = $this->createForm(DecisionType::class, $dossier);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var SubmitButton $cancel */
            $cancel = $form->get('cancel');
            if (! $cancel->isClicked()) {
                $this->workflow->updateDecision($dossier, $form);
            }

            return $this->redirectToRoute('app_admin_dossier', ['dossierId' => $dossier->getDossierNr()]);
        }

        $breadcrumbs->addRouteItem($dossier->getTitle() ?? '', 'app_admin_dossier', ['dossierId' => $dossier->getDossierNr()]);
        $breadcrumbs->addItem('workflow_step_decision');

        return $this->render('admin/dossier/edit/decision.html.twig', [
            'breadcrumbs' => $breadcrumbs,
            'dossier' => $dossier,
            'workflowStatus' => $workflowStatus,
            'form' => $form,
        ]);
    }

    #[Route('/balie/dossier/{dossierId}/edit/documents', name: 'app_admin_documents', methods: ['GET', 'POST'])]
    #[AuthMatrix('document.update')]
    public function documents(
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier,
        Request $request,
        Breadcrumbs $breadcrumbs
    ): Response {
        $this->testIfDossierIsAllowedByUser($dossier);

        $workflowStatus = $this->workflow->getStatus($dossier, StepName::DOCUMENTS);
        $currentStep = $workflowStatus->getCurrentStep();

        if ($workflowStatus->isConcept()) {
            return $this->redirectToRoute($currentStep->getConceptRouteName(), ['dossierId' => $dossier->getDossierNr()]);
        }

        $query = $this->documentRepository->getDossierDocumentsQueryBuilder($dossier);

        $pagination = $this->paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            20,
        );

        $breadcrumbs->addRouteItem($dossier->getTitle() ?? '', 'app_admin_dossier', ['dossierId' => $dossier->getDossierNr()]);
        $breadcrumbs->addItem('workflow_step_documents');

        $dataPath = null;
        if ($dossier->getProcessRun()?->isNotFinal()) {
            $dataPath = 'app_admin_dossier_inventory_status';
        }

        return $this->render('admin/dossier/edit/documents.html.twig', [
            'breadcrumbs' => $breadcrumbs,
            'dossier' => $dossier,
            'workflowStatus' => $workflowStatus,
            'pagination' => $pagination,
            'dataPath' => $dataPath,
        ]);
    }

    #[Route('/balie/dossier/{dossierId}/inventory-status', name: 'app_admin_dossier_inventory_status', methods: ['GET'])]
    #[AuthMatrix('dossier.update')]
    public function inventoryProcess(
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier,
    ): Response {
        $this->testIfDossierIsAllowedByUser($dossier);

        $inventoryForm = $this->createForm(InventoryType::class);

        $processRun = $dossier->getProcessRun();
        if ($processRun && $processRun->isFailed()) {
            $this->formErrorMapper->mapRunErrorsToForm($processRun, $inventoryForm);
        }

        $inventoryStatus = new InventoryStatus($dossier);

        return new JsonResponse([
            'content' => $this->renderView('admin/dossier/form/processrun.html.twig', [
                'dossier' => $dossier,
                'processRun' => $processRun,
                'inventoryForm' => $inventoryForm,
                'inventoryStatus' => $inventoryStatus,
                'ajax' => true,
            ]),
            'inventoryStatus' => [
                'canUpload' => $inventoryStatus->canUpload(),
                'hasErrors' => $inventoryStatus->hasErrors(),
                'isQueued' => $inventoryStatus->isQueued(),
                'isRunning' => $inventoryStatus->isRunning(),
                'needsUpdate' => $inventoryStatus->needsUpdate(),
            ],
        ]);
    }

    #[Route('/balie/dossier/{dossierId}/replace-inventory', name: 'app_admin_dossier_replace_inventory', methods: ['GET', 'POST'])]
    #[AuthMatrix('dossier.update')]
    public function replaceInventory(
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier,
        Request $request,
        Breadcrumbs $breadcrumbs
    ): Response {
        $this->testIfDossierIsAllowedByUser($dossier);

        $workflowStatus = $this->workflow->getStatus($dossier, StepName::DOCUMENTS);
        $currentStep = $workflowStatus->getCurrentStep();

        if ($workflowStatus->isConcept()) {
            return $this->redirectToRoute($currentStep->getConceptRouteName(), ['dossierId' => $dossier->getDossierNr()]);
        }

        $form = $this->createForm(InventoryType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->workflow->updateInventory($dossier, $form);
        }

        if (intval($request->get('confirm')) === 1) {
            $this->workflow->confirmInventoryUpdate($dossier);
        }

        if (intval($request->get('reject')) === 1) {
            $this->workflow->rejectInventoryUpdate($dossier);

            return $this->redirectToRoute('app_admin_documents', ['dossierId' => $dossier->getDossierNr()]);
        }

        $processRun = $dossier->getProcessRun();
        if ($processRun && $processRun->isFailed()) {
            $this->formErrorMapper->mapRunErrorsToForm($processRun, $form);
        }

        $dataPath = null;
        if ($processRun?->isNotFinal()) {
            $dataPath = 'app_admin_dossier_inventory_status';
        }

        $breadcrumbs->addRouteItem($dossier->getTitle() ?? '', 'app_admin_dossier', ['dossierId' => $dossier->getDossierNr()]);
        $breadcrumbs->addRouteItem('workflow_step_documents', 'app_admin_documents', ['dossierId' => $dossier->getDossierNr()]);
        $breadcrumbs->addItem('Replace inventory');

        return $this->render('admin/dossier/edit/inventory.html.twig', [
            'breadcrumbs' => $breadcrumbs,
            'dossier' => $dossier,
            'processRun' => $processRun,
            'workflowStatus' => $workflowStatus,
            'inventoryForm' => $form,
            'inventoryStatus' => new InventoryStatus($dossier),
            'dataPath' => $dataPath,
        ]);
    }

    #[Route('/balie/dossier/{dossierId}/edit/publication', name: 'app_admin_dossier_edit_publication', methods: ['GET', 'POST'])]
    #[AuthMatrix('dossier.update')]
    public function publication(
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier,
        Request $request,
        Breadcrumbs $breadcrumbs
    ): Response {
        $this->testIfDossierIsAllowedByUser($dossier);

        $workflowStatus = $this->workflow->getStatus($dossier, StepName::PUBLICATION);
        $currentStep = $workflowStatus->getCurrentStep();

        if ($workflowStatus->isConcept()) {
            return $this->redirectToRoute($currentStep->getConceptRouteName(), ['dossierId' => $dossier->getDossierNr()]);
        }

        $form = $this->createForm(PublishType::class, $dossier);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->workflow->publish($dossier);

            return $this->redirectToRoute('app_admin_dossier', ['dossierId' => $dossier->getDossierNr()]);
        }

        $breadcrumbs->addRouteItem($dossier->getTitle() ?? '', 'app_admin_dossier', ['dossierId' => $dossier->getDossierNr()]);
        $breadcrumbs->addItem('workflow_step_publication');

        return $this->render('admin/dossier/edit/publication.html.twig', [
            'breadcrumbs' => $breadcrumbs,
            'dossier' => $dossier,
            'workflowStatus' => $workflowStatus,
            'form' => $form,
        ]);
    }

    #[Route('/balie/dossier/{dossierId}/search', name: 'app_admin_dossier_documents_search', methods: ['POST'])]
    #[AuthMatrix('dossier.read')]
    public function search(
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier,
        Request $request
    ): Response {
        $this->testIfDossierIsAllowedByUser($dossier);

        $searchTerm = urldecode(strval($request->getPayload()->get('q', '')));

        $documents = $this->documentRepository->findForDossierBySearchTerm($dossier, $searchTerm, 4);

        $ret = [
            'results' => json_encode(
                $this->renderView(
                    'admin/dossier/search.html.twig',
                    [
                        'documents' => $documents,
                        'searchTerm' => $searchTerm,
                    ],
                ),
                JSON_THROW_ON_ERROR,
            ),
        ];

        return new JsonResponse($ret);
    }

    #[Route('/balie/dossier/{dossierId}/documenten-intrekken', name: 'app_admin_dossier_withdraw_all_documents', methods: ['GET', 'POST'])]
    #[AuthMatrix('dossier.update')]
    public function withdrawAllDocuments(
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier,
        Request $request,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $this->testIfDossierIsAllowedByUser($dossier);

        $form = $this->createForm(WithdrawFormType::class);
        $form->handleRequest($request);

        // When the cancel button is clicked, redirect back to the dossier
        /** @var SubmitButton $cancelButton */
        $cancelButton = $form->get('cancel');
        if ($cancelButton->isClicked()) {
            return $this->redirectToRoute('app_admin_dossier', ['dossierId' => $dossier->getDossierNr()]);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var WithdrawReason $reason */
            $reason = $form->get('reason')->getData();

            /** @var string $explanation */
            $explanation = $form->get('explanation')->getData();

            $this->workflow->withdrawAllDocuments($dossier, $reason, $explanation);

            return $this->redirectToRoute('app_admin_documents', ['dossierId' => $dossier->getDossierNr()]);
        }

        $breadcrumbs->addRouteItem($dossier->getTitle() ?? '', 'app_admin_dossier', ['dossierId' => $dossier->getDossierNr()]);
        $breadcrumbs->addItem('Withdraw all documents');

        return $this->render('admin/dossier/edit/withdraw-all-documents.html.twig', [
            'dossier' => $dossier,
            'breadcrumbs' => $breadcrumbs,
            'form' => $form,
        ]);
    }

    #[Route('/balie/dossier/{dossierId}/delete', name: 'app_admin_dossier_delete', methods: ['GET', 'POST'])]
    #[AuthMatrix('dossier.delete')]
    public function delete(
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier,
        Request $request,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $this->testIfDossierIsAllowedByUser($dossier);

        $success = false;
        $form = $this->createForm(DeleteFormType::class);
        $form->handleRequest($request);

        // When the cancel button is clicked, redirect back to the dossier
        /** @var SubmitButton $cancelButton */
        $cancelButton = $form->get('cancel');
        if ($cancelButton->isClicked()) {
            return $this->redirectToRoute('app_admin_dossier', ['dossierId' => $dossier->getDossierNr()]);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $this->workflow->delete($dossier);

            $success = true;
        }

        $breadcrumbs->addRouteItem($dossier->getTitle() ?? '', 'app_admin_dossier', ['dossierId' => $dossier->getDossierNr()]);
        $breadcrumbs->addItem('Delete dossier');

        return $this->render('admin/dossier/edit/delete.html.twig', [
            'dossier' => $dossier,
            'breadcrumbs' => $breadcrumbs,
            'form' => $form->createView(),
            'success' => $success,
        ]);
    }
}
