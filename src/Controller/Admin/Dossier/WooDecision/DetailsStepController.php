<?php

declare(strict_types=1);

namespace App\Controller\Admin\Dossier\WooDecision;

use App\Domain\Publication\Dossier\DossierFactory;
use App\Domain\Publication\Dossier\Step\StepActionHelper;
use App\Domain\Publication\Dossier\Step\StepName;
use App\Domain\Publication\Dossier\Type\DossierType;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Publication\Dossier\ViewModel\DossierFormParamBuilder;
use App\Form\Dossier\WooDecision\DetailsType;
use App\Service\DossierWizard\DossierWizardHelper;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

class DetailsStepController extends AbstractController
{
    public function __construct(
        private readonly DossierWizardHelper $wizardHelper,
        private readonly DossierFactory $dossierFactory,
        private readonly StepActionHelper $stepHelper,
        private readonly DossierFormParamBuilder $formParamBuilder,
    ) {
    }

    #[Route(
        path: '/balie/dossier/woodecision/details/create',
        name: 'app_admin_dossier_woodecision_details_create',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('AuthMatrix.dossier.create')]
    public function create(Request $request): Response
    {
        /** @var WooDecision $dossier */
        $dossier = $this->dossierFactory->create(DossierType::WOO_DECISION);

        $form = $this->getForm($dossier);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->wizardHelper->create($dossier);

            $wizardStatus = $this->wizardHelper->getStatus($dossier);

            return $this->stepHelper->redirectAfterFormSubmit($wizardStatus, $form);
        }

        return $this->render('admin/dossier/woo-decision/details/concept.html.twig', [
            'dossier' => $dossier,
            'workflowStatus' => $this->wizardHelper->getStatus($dossier),
            'form' => $form,
            'departments' => $this->formParamBuilder->getDepartmentsFieldParams($dossier, $form),
        ]);
    }

    #[Route(
        path: '/balie/dossier/woodecision/details/concept/{prefix}/{dossierId}',
        name: 'app_admin_dossier_woodecision_details_concept',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('AuthMatrix.dossier.create', subject: 'dossier')]
    public function concept(
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])] WooDecision $dossier,
        Request $request,
    ): Response {
        $stepName = StepName::DETAILS;

        $wizardStatus = $this->stepHelper->getWizardStatus($dossier, $stepName);
        if (! $wizardStatus->isCurrentStepAccessibleInConceptMode()) {
            return $this->stepHelper->redirectToFirstOpenStep($wizardStatus);
        }

        $form = $this->getForm($dossier);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->wizardHelper->updateDetails($dossier);

            return $this->stepHelper->redirectAfterFormSubmit($wizardStatus, $form);
        }

        return $this->render('admin/dossier/woo-decision/details/concept.html.twig', [
            'dossier' => $dossier,
            'workflowStatus' => $wizardStatus,
            'form' => $form,
            'departments' => $this->formParamBuilder->getDepartmentsFieldParams($dossier, $form),
        ]);
    }

    #[Route(
        path: '/balie/dossier/woodecision/details/edit/{prefix}/{dossierId}',
        name: 'app_admin_dossier_woodecision_details_edit',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('AuthMatrix.dossier.update', subject: 'dossier')]
    public function edit(
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])] WooDecision $dossier,
        Request $request,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $this->stepHelper->addDossierToBreadcrumbs($breadcrumbs, $dossier, 'admin.dossiers.woo-decision.step.details');

        $stepName = StepName::DETAILS;

        $wizardStatus = $this->stepHelper->getWizardStatus($dossier, $stepName);
        if (! $wizardStatus->isCurrentStepAccessibleInEditMode()) {
            return $this->stepHelper->redirectToDossier($dossier);
        }

        $form = $this->getForm($dossier);

        $form->handleRequest($request);
        if ($this->stepHelper->isFormCancelled($form)) {
            return $this->stepHelper->redirectToDossier($dossier);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $this->wizardHelper->updateDetails($dossier);

            return $this->stepHelper->redirectToDossier($dossier);
        }

        return $this->render('admin/dossier/woo-decision/details/edit.html.twig', [
            'breadcrumbs' => $breadcrumbs,
            'dossier' => $dossier,
            'workflowStatus' => $wizardStatus,
            'form' => $form,
            'departments' => $this->formParamBuilder->getDepartmentsFieldParams($dossier, $form),
        ]);
    }

    private function getForm(WooDecision $dossier): FormInterface
    {
        return $this->createForm(
            DetailsType::class,
            $dossier,
            ['validation_groups' => [StepName::DETAILS->value]],
        );
    }
}
