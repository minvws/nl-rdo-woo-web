<?php

declare(strict_types=1);

namespace App\Controller\Admin\Dossier\AnnualReport;

use App\Domain\Publication\Dossier\Command\UpdateDossierPublicationCommand;
use App\Domain\Publication\Dossier\Step\StepActionHelper;
use App\Domain\Publication\Dossier\Step\StepName;
use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReport;
use App\Form\Dossier\AnnualReport\PublishType;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

class PublicationStepController extends AbstractController
{
    private const STEP_NAME = StepName::PUBLICATION;

    public function __construct(
        private readonly StepActionHelper $stepHelper,
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    #[Route(
        path: '/balie/dossier/annual-report/publish/concept/{prefix}/{dossierId}',
        name: 'app_admin_dossier_annualreport_publication_concept',
        methods: ['GET', 'POST']
    )]
    #[IsGranted('AuthMatrix.dossier.create', subject: 'dossier')]
    public function concept(
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])] AnnualReport $dossier,
        Request $request,
    ): Response {
        $wizardStatus = $this->stepHelper->getWizardStatus($dossier, self::STEP_NAME);
        if (! $wizardStatus->isCurrentStepAccessibleInConceptMode()) {
            return $this->stepHelper->redirectToFirstOpenStep($wizardStatus);
        }

        $form = $this->createForm(PublishType::class, $dossier);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->messageBus->dispatch(
                new UpdateDossierPublicationCommand($dossier),
            );

            return $this->stepHelper->redirectToPublicationConfirmation($dossier);
        }

        return $this->render('admin/dossier/annual-report/publication/concept.html.twig', [
            'dossier' => $dossier,
            'workflowStatus' => $wizardStatus,
            'form' => $form,
        ]);
    }

    #[Route(
        path: '/balie/dossier/annual-report/publish/edit/{prefix}/{dossierId}',
        name: 'app_admin_dossier_annualreport_publication_edit',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('AuthMatrix.dossier.update', subject: 'dossier')]
    public function edit(
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])] AnnualReport $dossier,
        Request $request,
        Breadcrumbs $breadcrumbs
    ): Response {
        $wizardStatus = $this->stepHelper->getWizardStatus($dossier, self::STEP_NAME);
        if (! $wizardStatus->isCurrentStepAccessibleInEditMode()) {
            return $this->stepHelper->redirectToDossier($dossier);
        }

        $form = $this->createForm(PublishType::class, $dossier);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->messageBus->dispatch(
                new UpdateDossierPublicationCommand($dossier),
            );

            return $this->stepHelper->redirectToPublicationConfirmation($dossier);
        }

        $this->stepHelper->addDossierToBreadcrumbs($breadcrumbs, $dossier, 'admin.dossiers.annual-report.step.publication');

        return $this->render('admin/dossier/annual-report/publication/edit.html.twig', [
            'breadcrumbs' => $breadcrumbs,
            'dossier' => $dossier,
            'workflowStatus' => $wizardStatus,
            'form' => $form,
        ]);
    }
}
