<?php

declare(strict_types=1);

namespace Shared\Controller\Admin\Dossier\RequestForAdvice;

use Shared\Domain\Publication\Dossier\DossierDispatcher;
use Shared\Domain\Publication\Dossier\Step\StepActionHelper;
use Shared\Domain\Publication\Dossier\Step\StepName;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdvice;
use Shared\Form\Dossier\RequestForAdvice\ContentFormType;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

class ContentStepController extends AbstractController
{
    private const STEP_NAME = StepName::CONTENT;

    public function __construct(
        private readonly StepActionHelper $stepHelper,
        private readonly DossierDispatcher $dossierDispatcher,
    ) {
    }

    #[Route(
        path: '/balie/dossier/request-for-advice/content/concept/{prefix}/{dossierId}',
        name: 'app_admin_dossier_requestforadvice_content_concept',
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
        $breadcrumbs->addItem('admin.dossiers.request-for-advice.step.content');

        $wizardStatus = $this->stepHelper->getWizardStatus($dossier, self::STEP_NAME);
        if (! $wizardStatus->isCurrentStepAccessibleInConceptMode()) {
            return $this->stepHelper->redirectToFirstOpenStep($wizardStatus);
        }

        $form = $this->getForm($dossier);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->dossierDispatcher->dispatchUpdateDossierContentCommand($dossier);

            return $this->stepHelper->redirectAfterFormSubmit($wizardStatus, $form);
        }

        return $this->render(
            'admin/dossier/request-for-advice/content/concept.html.twig',
            $this->stepHelper->getParamsBuilder($dossier)
                ->withMainDocumentParams($dossier)
                ->withAttachmentsParams($dossier)
                ->withForm($form)
                ->withWizardStatus($wizardStatus)
                ->withBreadCrumbs($breadcrumbs)
                ->withDepartments()
                ->with('advisoryBodiesErrors', $this->getAdvisoryBodiesErrors($form))
                ->getParams()
        );
    }

    #[Route(
        path: '/balie/dossier/request-for-advice/content/edit/{prefix}/{dossierId}',
        name: 'app_admin_dossier_requestforadvice_content_edit',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('AuthMatrix.dossier.update', subject: 'dossier')]
    public function edit(
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])] RequestForAdvice $dossier,
        Request $request,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $this->stepHelper->addDossierToBreadcrumbs($breadcrumbs, $dossier, 'admin.dossiers.request-for-advice.step.content');

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
            $this->dossierDispatcher->dispatchUpdateDossierContentCommand($dossier);

            return $this->stepHelper->redirectToDossier($dossier);
        }

        return $this->render(
            'admin/dossier/request-for-advice/content/edit.html.twig',
            $this->stepHelper->getParamsBuilder($dossier)
                ->withMainDocumentParams($dossier)
                ->withAttachmentsParams($dossier)
                ->withForm($form)
                ->withWizardStatus($wizardStatus)
                ->withBreadCrumbs($breadcrumbs)
                ->withDepartments()
                ->with('advisoryBodiesErrors', $this->getAdvisoryBodiesErrors($form))
                ->getParams()
        );
    }

    private function getForm(RequestForAdvice $dossier): FormInterface
    {
        return $this->createForm(
            ContentFormType::class,
            $dossier,
            ['validation_groups' => [self::STEP_NAME->value]]
        );
    }

    /**
     * @return string[]
     */
    private function getAdvisoryBodiesErrors(FormInterface $form): array
    {
        $errors = [];
        foreach ($form->get('advisoryBodies')->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }

        return $errors;
    }
}
