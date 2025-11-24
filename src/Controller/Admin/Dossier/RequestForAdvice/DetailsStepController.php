<?php

declare(strict_types=1);

namespace Shared\Controller\Admin\Dossier\RequestForAdvice;

use Shared\Domain\Publication\Dossier\DossierDispatcher;
use Shared\Domain\Publication\Dossier\DossierFactory;
use Shared\Domain\Publication\Dossier\Step\StepActionHelper;
use Shared\Domain\Publication\Dossier\Step\StepName;
use Shared\Domain\Publication\Dossier\Type\DossierType;
use Shared\Domain\Publication\Dossier\Type\DossierValidationGroup;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdvice;
use Shared\Domain\Publication\Dossier\ViewModel\DossierFormParamBuilder;
use Shared\Form\Dossier\RequestForAdvice\DetailsType;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

/**
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
class DetailsStepController extends AbstractController
{
    private const STEP_NAME = StepName::DETAILS;

    public function __construct(
        private readonly DossierFactory $dossierFactory,
        private readonly StepActionHelper $stepHelper,
        private readonly DossierDispatcher $dossierDispatcher,
        private readonly DossierFormParamBuilder $formParamBuilder,
    ) {
    }

    #[Route(
        path: '/balie/dossier/request-for-advice/details/create',
        name: 'app_admin_dossier_requestforadvice_details_create',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('AuthMatrix.dossier.create')]
    public function create(Request $request): Response
    {
        /** @var RequestForAdvice $dossier */
        $dossier = $this->dossierFactory->create(DossierType::REQUEST_FOR_ADVICE);

        $form = $this->getForm($dossier);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->dossierDispatcher->dispatchCreateDossierCommand($dossier);

            $wizardStatus = $this->stepHelper->getWizardStatus($dossier, self::STEP_NAME);

            return $this->stepHelper->redirectAfterFormSubmit($wizardStatus, $form);
        }

        return $this->render('admin/dossier/request-for-advice/details/concept.html.twig', [
            'dossier' => $dossier,
            'workflowStatus' => $this->stepHelper->getWizardStatus($dossier, self::STEP_NAME),
            'form' => $form,
            'departments' => $this->formParamBuilder->getDepartmentsFieldParams($dossier, $form),
        ]);
    }

    #[Route(
        path: '/balie/dossier/request-for-advice/details/concept/{prefix}/{dossierId}',
        name: 'app_admin_dossier_requestforadvice_details_concept',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('AuthMatrix.dossier.create', subject: 'dossier')]
    public function concept(
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])] RequestForAdvice $dossier,
        Request $request,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $breadcrumbs->addRouteItem(
            $dossier->getTitle() ?? '',
            'app_admin_dossier',
            ['prefix' => $dossier->getDocumentPrefix(), 'dossierId' => $dossier->getDossierNr()]
        );
        $breadcrumbs->addItem('admin.dossiers.request-for-advice.step.details');

        $wizardStatus = $this->stepHelper->getWizardStatus($dossier, self::STEP_NAME);
        if (! $wizardStatus->isCurrentStepAccessibleInConceptMode()) {
            return $this->stepHelper->redirectToFirstOpenStep($wizardStatus);
        }

        $form = $this->getForm($dossier);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->dossierDispatcher->dispatchUpdateDossierDetailsCommand($dossier);

            return $this->stepHelper->redirectAfterFormSubmit($wizardStatus, $form);
        }

        return $this->render('admin/dossier/request-for-advice/details/concept.html.twig', [
            'dossier' => $dossier,
            'workflowStatus' => $wizardStatus,
            'form' => $form,
            'departments' => $this->formParamBuilder->getDepartmentsFieldParams($dossier, $form),
        ]);
    }

    #[Route(
        path: '/balie/dossier/request-for-advice/details/edit/{prefix}/{dossierId}',
        name: 'app_admin_dossier_requestforadvice_details_edit',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('AuthMatrix.dossier.update', subject: 'dossier')]
    public function edit(
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])] RequestForAdvice $dossier,
        Request $request,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $this->stepHelper->addDossierToBreadcrumbs($breadcrumbs, $dossier, 'admin.dossiers.request-for-advice.step.details');

        $wizardStatus = $this->stepHelper->getWizardStatus($dossier, self::STEP_NAME);
        if (! $wizardStatus->isCurrentStepAccessibleInEditMode()) {
            return $this->stepHelper->redirectToDossier($dossier);
        }

        $form = $this->getForm($dossier);

        $form->handleRequest($request);
        if ($this->stepHelper->isFormCancelled($form)) {
            return $this->stepHelper->redirectToDossier($dossier);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $this->dossierDispatcher->dispatchUpdateDossierDetailsCommand($dossier);

            return $this->stepHelper->redirectToDossier($dossier);
        }

        return $this->render('admin/dossier/request-for-advice/details/edit.html.twig', [
            'breadcrumbs' => $breadcrumbs,
            'dossier' => $dossier,
            'workflowStatus' => $wizardStatus,
            'form' => $form,
            'departments' => $this->formParamBuilder->getDepartmentsFieldParams($dossier, $form),
        ]);
    }

    private function getForm(RequestForAdvice $dossier): FormInterface
    {
        return $this->createForm(
            DetailsType::class,
            $dossier,
            [
                'validation_groups' => [
                    self::STEP_NAME->value,
                    DossierValidationGroup::REQUEST_FOR_ADVICE_DETAILS->value,
                ],
            ],
        );
    }
}
