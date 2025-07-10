<?php

declare(strict_types=1);

namespace App\Controller\Admin\Dossier\WooDecision;

use App\Domain\Publication\Attachment\ViewModel\AttachmentViewFactory;
use App\Domain\Publication\Dossier\Step\StepActionHelper;
use App\Domain\Publication\Dossier\Step\StepName;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecisionDispatcher;
use App\Form\Dossier\WooDecision\DecisionType;
use App\Service\Security\ApplicationMode\ApplicationMode;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

/**
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
class DecisionStepController extends AbstractController
{
    public function __construct(
        private readonly StepActionHelper $stepHelper,
        private readonly AttachmentViewFactory $attachmentViewFactory,
        private readonly WooDecisionDispatcher $dispatcher,
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
        // Ensure dossier status is new/concept and validate step accessibility
        if (! $wizardStatus->isCurrentStepAccessibleInConceptMode()) {
            return $this->stepHelper->redirectToFirstOpenStep($wizardStatus);
        }

        $form = $this->createForm(
            DecisionType::class,
            $dossier,
            ['validation_groups' => [$stepName->value]],
        );

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->dispatcher->dispatchUpdateDecisionCommand($dossier);

            return $this->stepHelper->redirectAfterFormSubmit($wizardStatus, $form);
        }

        return $this->render(
            'admin/dossier/woo-decision/decision/concept.html.twig',
            $this->stepHelper->getParamsBuilder($dossier)
                ->withMainDocumentParams($dossier)
                ->withAttachmentsParams($dossier)
                ->withForm($form)
                ->withWizardStatus($wizardStatus)
                ->withDepartments()
                ->with('attachments', $this->attachmentViewFactory->makeCollection(
                    $dossier,
                    ApplicationMode::ADMIN
                ))
                ->getParams()
        );
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
        Breadcrumbs $breadcrumbs,
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

        if ($form->isSubmitted() && $form->isValid()) {
            $this->dispatcher->dispatchUpdateDecisionCommand($dossier);

            return $this->stepHelper->redirectToDossier($dossier);
        }

        $this->stepHelper->addDossierToBreadcrumbs($breadcrumbs, $dossier, 'admin.dossiers.woo-decision.step.decision');

        return $this->render(
            'admin/dossier/woo-decision/decision/edit.html.twig',
            $this->stepHelper->getParamsBuilder($dossier)
                ->withMainDocumentParams($dossier)
                ->withAttachmentsParams($dossier)
                ->withForm($form)
                ->withWizardStatus($wizardStatus)
                ->withDepartments()
                ->with('attachments', $this->attachmentViewFactory->makeCollection(
                    $dossier,
                    ApplicationMode::ADMIN
                ))
                ->getParams()
        );
    }
}
