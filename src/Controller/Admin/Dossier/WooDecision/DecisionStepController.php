<?php

declare(strict_types=1);

namespace App\Controller\Admin\Dossier\WooDecision;

use App\Domain\Publication\Attachment\AttachmentLanguageFactory;
use App\Domain\Publication\Attachment\AttachmentTypeFactory;
use App\Domain\Publication\Dossier\Step\StepActionHelper;
use App\Domain\Publication\Dossier\Step\StepName;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use App\Form\Dossier\WooDecision\DecisionType;
use App\Service\DossierWizard\DossierWizardHelper;
use App\ViewModel\Factory\ApplicationMode;
use App\ViewModel\Factory\AttachmentViewFactory;
use App\ViewModel\Factory\GroundViewFactory;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DecisionStepController extends AbstractController
{
    public function __construct(
        private readonly DossierWizardHelper $wizardHelper,
        private readonly StepActionHelper $stepHelper,
        private readonly AttachmentViewFactory $attachmentViewFactory,
        private readonly AttachmentTypeFactory $attachmentTypeFactory,
        private readonly AttachmentLanguageFactory $attachmentLanguageFactory,
        private readonly DossierWorkflowManager $dossierWorkflowManager,
        private readonly GroundViewFactory $groundViewFactory,
    ) {
    }

    #[Route(
        path: '/balie/dossier/woodecision/decision/concept/{prefix}/{dossierId}',
        name: 'app_admin_dossier_woodecision_decision_concept',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('AuthMatrix.dossier.create', subject: 'dossier')]
    public function concept(
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])] WooDecision $dossier,
        Request $request,
    ): Response {
        $stepName = StepName::DECISION;

        $wizardStatus = $this->stepHelper->getWizardStatus($dossier, $stepName);
        // Ensure dossier status is new/concept and validate step accessibility TODO
        if (! $wizardStatus->isCurrentStepAccessibleInConceptMode()) {
            return $this->stepHelper->redirectToFirstOpenStep($wizardStatus);
        }

        $form = $this->createForm(
            DecisionType::class,
            $dossier,
            ['validation_groups' => [$stepName->value]],
        );

        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            // Always handle the upload if it is available, even if another field might still have some error.
            /** @var ?UploadedFile $uploadedFile */
            $uploadedFile = $form->get('decision_document')->getData();
            if ($uploadedFile instanceof UploadedFile) {
                $this->wizardHelper->updateDecisionDocument($dossier, $uploadedFile);

                // Renew the form instance so validation on decisionDocument is updated
                $form = $this->createForm(
                    DecisionType::class,
                    $dossier,
                    ['validation_groups' => [$stepName->value]],
                );
                $form->handleRequest($request);
            }

            if ($form->isValid()) {
                $this->wizardHelper->updateDecision($dossier);

                return $this->stepHelper->redirectAfterFormSubmit($wizardStatus, $form);
            }
        }

        return $this->render('admin/dossier/woo-decision/decision/concept.html.twig', [
            'dossier' => $dossier,
            'workflowStatus' => $wizardStatus,
            'form' => $form,
            'attachmentTypes' => $this->attachmentTypeFactory->makeAsArray(),
            'attachmentLanguages' => $this->attachmentLanguageFactory->makeAsArray(),
            'grounds' => $this->groundViewFactory->makeAsArray(),
            'attachments' => $this->attachmentViewFactory->makeCollection(
                $dossier,
                ApplicationMode::ADMIN
            ),
            'canDeleteAttachments' => $this->dossierWorkflowManager->isTransitionAllowed(
                $dossier,
                DossierStatusTransition::DELETE_DECISION_ATTACHMENT,
            ),
        ]);
    }

    #[Route(
        path: '/balie/dossier/woodecision/decision/edit/{prefix}/{dossierId}',
        name: 'app_admin_dossier_woodecision_decision_edit',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('AuthMatrix.dossier.update', subject: 'dossier')]
    public function edit(
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])] WooDecision $dossier,
        Request $request,
        Breadcrumbs $breadcrumbs
    ): Response {
        $stepName = StepName::DECISION;

        $wizardStatus = $this->stepHelper->getWizardStatus($dossier, $stepName);
        if (! $wizardStatus->isCurrentStepAccessibleInEditMode()) {
            return $this->stepHelper->redirectToDossier($dossier);
        }

        $form = $this->createForm(
            DecisionType::class,
            $dossier,
            ['validation_groups' => [$stepName->value]],
        );

        $form->handleRequest($request);
        if ($this->stepHelper->isFormCancelled($form)) {
            return $this->stepHelper->redirectToDossier($dossier);
        }

        if ($form->isSubmitted()) {
            // Always handle the upload if it is available, even if another field might still have some error.
            /** @var ?UploadedFile $uploadedFile */
            $uploadedFile = $form->get('decision_document')->getData();
            if ($uploadedFile instanceof UploadedFile) {
                $this->wizardHelper->updateDecisionDocument($dossier, $uploadedFile);

                // Renew the form instance so validation on decisionDocument is updated
                $form = $this->createForm(
                    DecisionType::class,
                    $dossier,
                    ['validation_groups' => [$stepName->value]],
                );
                $form->handleRequest($request);
            }

            if ($form->isValid()) {
                $this->wizardHelper->updateDecision($dossier);

                return $this->stepHelper->redirectToDossier($dossier);
            }
        }

        $this->stepHelper->addDossierToBreadcrumbs($breadcrumbs, $dossier, 'workflow_step_decision');

        return $this->render('admin/dossier/woo-decision/decision/edit.html.twig', [
            'breadcrumbs' => $breadcrumbs,
            'dossier' => $dossier,
            'workflowStatus' => $wizardStatus,
            'form' => $form,
            'attachmentTypes' => $this->attachmentTypeFactory->makeAsArray(),
            'attachmentLanguages' => $this->attachmentLanguageFactory->makeAsArray(),
            'grounds' => $this->groundViewFactory->makeAsArray(),
            'attachments' => $this->attachmentViewFactory->makeCollection(
                $dossier,
                ApplicationMode::ADMIN
            ),
            'canDeleteAttachments' => $this->dossierWorkflowManager->isTransitionAllowed(
                $dossier,
                DossierStatusTransition::DELETE_DECISION_ATTACHMENT,
            ),
        ]);
    }
}
