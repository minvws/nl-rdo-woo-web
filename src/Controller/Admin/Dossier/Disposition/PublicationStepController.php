<?php

declare(strict_types=1);

namespace App\Controller\Admin\Dossier\Disposition;

use App\Domain\Publication\Dossier\DossierDispatcher;
use App\Domain\Publication\Dossier\Step\StepActionHelper;
use App\Domain\Publication\Dossier\Step\StepName;
use App\Domain\Publication\Dossier\Type\Disposition\Disposition;
use App\Form\Dossier\Disposition\PublishType;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

class PublicationStepController extends AbstractController
{
    private const STEP_NAME = StepName::PUBLICATION;

    public function __construct(
        private readonly StepActionHelper $stepHelper,
        private readonly DossierDispatcher $dossierDispatcher,
    ) {
    }

    #[Route(
        path: '/balie/dossier/disposition/publish/concept/{prefix}/{dossierId}',
        name: 'app_admin_dossier_disposition_publication_concept',
        methods: ['GET', 'POST']
    )]
    #[IsGranted('AuthMatrix.dossier.create', subject: 'dossier')]
    public function concept(
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])] Disposition $dossier,
        Request $request,
    ): Response {
        $wizardStatus = $this->stepHelper->getWizardStatus($dossier, self::STEP_NAME);
        if (! $wizardStatus->isCurrentStepAccessibleInConceptMode()) {
            return $this->stepHelper->redirectToFirstOpenStep($wizardStatus);
        }

        $form = $this->createForm(PublishType::class, $dossier);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->dossierDispatcher->dispatchUpdateDossierPublicationCommand($dossier);

            return $this->stepHelper->redirectToPublicationConfirmation($dossier);
        }

        return $this->render('admin/dossier/disposition/publication/concept.html.twig', [
            'dossier' => $dossier,
            'workflowStatus' => $wizardStatus,
            'form' => $form,
        ]);
    }

    #[Route(
        path: '/balie/dossier/disposition/publish/edit/{prefix}/{dossierId}',
        name: 'app_admin_dossier_disposition_publication_edit',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('AuthMatrix.dossier.update', subject: 'dossier')]
    public function edit(
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])] Disposition $dossier,
        Request $request,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $wizardStatus = $this->stepHelper->getWizardStatus($dossier, self::STEP_NAME);
        if (! $wizardStatus->isCurrentStepAccessibleInEditMode()) {
            return $this->stepHelper->redirectToDossier($dossier);
        }

        $form = $this->createForm(PublishType::class, $dossier);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->dossierDispatcher->dispatchUpdateDossierPublicationCommand($dossier);

            return $this->stepHelper->redirectToPublicationConfirmation($dossier);
        }

        $this->stepHelper->addDossierToBreadcrumbs($breadcrumbs, $dossier, 'admin.dossiers.disposition.step.publication');

        return $this->render('admin/dossier/disposition/publication/edit.html.twig', [
            'breadcrumbs' => $breadcrumbs,
            'dossier' => $dossier,
            'workflowStatus' => $wizardStatus,
            'form' => $form,
        ]);
    }
}
