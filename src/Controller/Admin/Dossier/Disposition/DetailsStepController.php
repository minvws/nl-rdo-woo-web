<?php

declare(strict_types=1);

namespace App\Controller\Admin\Dossier\Disposition;

use App\Domain\Publication\Dossier\Command\CreateDossierCommand;
use App\Domain\Publication\Dossier\Command\UpdateDossierDetailsCommand;
use App\Domain\Publication\Dossier\DossierFactory;
use App\Domain\Publication\Dossier\Step\StepActionHelper;
use App\Domain\Publication\Dossier\Step\StepName;
use App\Domain\Publication\Dossier\Type\Disposition\Disposition;
use App\Domain\Publication\Dossier\Type\DossierType;
use App\Domain\Publication\Dossier\Type\DossierValidationGroup;
use App\Form\Dossier\Disposition\DetailsType;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DetailsStepController extends AbstractController
{
    private const STEP_NAME = StepName::DETAILS;

    public function __construct(
        private readonly DossierFactory $dossierFactory,
        private readonly StepActionHelper $stepHelper,
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    #[Route(
        path: '/balie/dossier/disposition/details/create',
        name: 'app_admin_dossier_disposition_details_create',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('AuthMatrix.dossier.create')]
    public function create(Request $request): Response
    {
        /** @var Disposition $dossier */
        $dossier = $this->dossierFactory->create(DossierType::DISPOSITION);

        $form = $this->getForm($dossier);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->messageBus->dispatch(
                new CreateDossierCommand($dossier),
            );

            $wizardStatus = $this->stepHelper->getWizardStatus($dossier, self::STEP_NAME);

            return $this->stepHelper->redirectAfterFormSubmit($wizardStatus, $form);
        }

        return $this->render('admin/dossier/disposition/details/concept.html.twig', [
            'dossier' => $dossier,
            'workflowStatus' => $this->stepHelper->getWizardStatus($dossier, self::STEP_NAME),
            'form' => $form,
        ]);
    }

    #[Route(
        path: '/balie/dossier/disposition/details/concept/{prefix}/{dossierId}',
        name: 'app_admin_dossier_disposition_details_concept',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('AuthMatrix.dossier.create', subject: 'dossier')]
    public function concept(
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])] Disposition $dossier,
        Request $request,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $breadcrumbs->addRouteItem(
            $dossier->getTitle() ?? '',
            'app_admin_dossier',
            ['prefix' => $dossier->getDocumentPrefix(), 'dossierId' => $dossier->getDossierNr()]
        );
        $breadcrumbs->addItem('admin.dossiers.disposition.step.details');

        $wizardStatus = $this->stepHelper->getWizardStatus($dossier, self::STEP_NAME);
        if (! $wizardStatus->isCurrentStepAccessibleInConceptMode()) {
            return $this->stepHelper->redirectToFirstOpenStep($wizardStatus);
        }

        $form = $this->getForm($dossier);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->messageBus->dispatch(
                new UpdateDossierDetailsCommand($dossier),
            );

            return $this->stepHelper->redirectAfterFormSubmit($wizardStatus, $form);
        }

        return $this->render('admin/dossier/disposition/details/concept.html.twig', [
            'dossier' => $dossier,
            'workflowStatus' => $wizardStatus,
            'form' => $form,
        ]);
    }

    #[Route(
        path: '/balie/dossier/disposition/details/edit/{prefix}/{dossierId}',
        name: 'app_admin_dossier_disposition_details_edit',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('AuthMatrix.dossier.update', subject: 'dossier')]
    public function edit(
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])] Disposition $dossier,
        Request $request,
        Breadcrumbs $breadcrumbs
    ): Response {
        $this->stepHelper->addDossierToBreadcrumbs($breadcrumbs, $dossier, 'admin.dossiers.disposition.step.details');

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
            $this->messageBus->dispatch(
                new UpdateDossierDetailsCommand($dossier),
            );

            return $this->stepHelper->redirectToDossier($dossier);
        }

        return $this->render('admin/dossier/disposition/details/edit.html.twig', [
            'breadcrumbs' => $breadcrumbs,
            'dossier' => $dossier,
            'workflowStatus' => $wizardStatus,
            'form' => $form,
        ]);
    }

    private function getForm(Disposition $dossier): FormInterface
    {
        return $this->createForm(
            DetailsType::class,
            $dossier,
            [
                'validation_groups' => [
                    self::STEP_NAME->value,
                    DossierValidationGroup::DISPOSITION_DETAILS->value,
                ],
            ],
        );
    }
}
