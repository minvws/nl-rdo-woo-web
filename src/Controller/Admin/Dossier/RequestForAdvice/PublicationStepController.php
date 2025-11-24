<?php

declare(strict_types=1);

namespace Shared\Controller\Admin\Dossier\RequestForAdvice;

use Shared\Domain\Publication\Dossier\DossierDispatcher;
use Shared\Domain\Publication\Dossier\Step\StepActionHelper;
use Shared\Domain\Publication\Dossier\Step\StepName;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdvice;
use Shared\Form\Dossier\RequestForAdvice\PublishType;
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
        path: '/balie/dossier/request-for-advice/publish/concept/{prefix}/{dossierId}',
        name: 'app_admin_dossier_requestforadvice_publication_concept',
        methods: ['GET', 'POST']
    )]
    #[IsGranted('AuthMatrix.dossier.create', subject: 'dossier')]
    public function concept(
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])] RequestForAdvice $dossier,
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

        return $this->render('admin/dossier/request-for-advice/publication/concept.html.twig', [
            'dossier' => $dossier,
            'workflowStatus' => $wizardStatus,
            'form' => $form,
        ]);
    }

    #[Route(
        path: '/balie/dossier/request-for-advice/publish/edit/{prefix}/{dossierId}',
        name: 'app_admin_dossier_requestforadvice_publication_edit',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('AuthMatrix.dossier.update', subject: 'dossier')]
    public function edit(
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])] RequestForAdvice $dossier,
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

        $this->stepHelper->addDossierToBreadcrumbs($breadcrumbs, $dossier, 'admin.dossiers.request-for-advice.step.publication');

        return $this->render('admin/dossier/request-for-advice/publication/edit.html.twig', [
            'breadcrumbs' => $breadcrumbs,
            'dossier' => $dossier,
            'workflowStatus' => $wizardStatus,
            'form' => $form,
        ]);
    }
}
