<?php

declare(strict_types=1);

namespace App\Controller\Admin\Dossier\InvestigationReport;

use App\Domain\Publication\Attachment\AttachmentLanguageFactory;
use App\Domain\Publication\Attachment\AttachmentTypeFactory;
use App\Domain\Publication\Dossier\Command\UpdateDossierContentCommand;
use App\Domain\Publication\Dossier\Step\StepActionHelper;
use App\Domain\Publication\Dossier\Step\StepName;
use App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport;
use App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportAttachment;
use App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportDocument;
use App\Domain\Publication\Dossier\ViewModel\GroundViewFactory;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use App\Form\Dossier\InvestigationReport\ContentFormType;
use App\Repository\DepartmentRepository;
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
class ContentStepController extends AbstractController
{
    private const STEP_NAME = StepName::CONTENT;

    public function __construct(
        private readonly StepActionHelper $stepHelper,
        private readonly DepartmentRepository $departmentRepository,
        private readonly DossierWorkflowManager $dossierWorkflowManager,
        private readonly AttachmentTypeFactory $attachmentTypeFactory,
        private readonly AttachmentLanguageFactory $attachmentLanguageFactory,
        private readonly GroundViewFactory $groundViewFactory,
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    #[Route(
        path: '/balie/dossier/investigation-report/content/concept/{prefix}/{dossierId}',
        name: 'app_admin_dossier_investigationreport_content_concept',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('AuthMatrix.dossier.create', subject: 'dossier')]
    public function concept(
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])] InvestigationReport $dossier,
        Request $request,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $breadcrumbs->addRouteItem(
            $dossier->getTitle() ?? '',
            'app_admin_dossier',
            ['prefix' => $dossier->getDocumentPrefix(), 'dossierId' => $dossier->getDossierNr()]
        );
        $breadcrumbs->addItem('admin.dossiers.investigation-report.step.content');

        $wizardStatus = $this->stepHelper->getWizardStatus($dossier, self::STEP_NAME);
        if (! $wizardStatus->isCurrentStepAccessibleInConceptMode()) {
            return $this->stepHelper->redirectToFirstOpenStep($wizardStatus);
        }

        $form = $this->getForm($dossier);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->messageBus->dispatch(
                new UpdateDossierContentCommand($dossier),
            );

            return $this->stepHelper->redirectAfterFormSubmit($wizardStatus, $form);
        }

        return $this->render('admin/dossier/investigation-report/content/concept.html.twig', [
            'dossier' => $dossier,
            'workflowStatus' => $wizardStatus,
            'form' => $form,
            'departments' => $this->departmentRepository->getNames(),
            'canDeleteDocument' => $this->dossierWorkflowManager->isTransitionAllowed(
                $dossier,
                DossierStatusTransition::DELETE_MAIN_DOCUMENT,
            ),
            'documentTypes' => $this->attachmentTypeFactory->makeAsArray(InvestigationReportDocument::getAllowedTypes()),
            'attachmentTypes' => $this->attachmentTypeFactory->makeAsArray(InvestigationReportAttachment::getAllowedTypes()),
            'attachmentLanguages' => $this->attachmentLanguageFactory->makeAsArray(),
            'grounds' => $this->groundViewFactory->makeAsArray(),
            'canDeleteAttachments' => $this->dossierWorkflowManager->isTransitionAllowed(
                $dossier,
                DossierStatusTransition::DELETE_ATTACHMENT,
            ),
        ]);
    }

    #[Route(
        path: '/balie/dossier/investigation-report/content/edit/{prefix}/{dossierId}',
        name: 'app_admin_dossier_investigationreport_content_edit',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('AuthMatrix.dossier.update', subject: 'dossier')]
    public function edit(
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])] InvestigationReport $dossier,
        Request $request,
        Breadcrumbs $breadcrumbs
    ): Response {
        $this->stepHelper->addDossierToBreadcrumbs($breadcrumbs, $dossier, 'admin.dossiers.investigation-report.step.content');

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
                new UpdateDossierContentCommand($dossier),
            );

            return $this->stepHelper->redirectToDossier($dossier);
        }

        return $this->render('admin/dossier/investigation-report/content/edit.html.twig', [
            'breadcrumbs' => $breadcrumbs,
            'dossier' => $dossier,
            'workflowStatus' => $wizardStatus,
            'form' => $form,
            'departments' => $this->departmentRepository->getNames(),
            'canDeleteDocument' => $this->dossierWorkflowManager->isTransitionAllowed(
                $dossier,
                DossierStatusTransition::DELETE_MAIN_DOCUMENT,
            ),
            'documentTypes' => $this->attachmentTypeFactory->makeAsArray(InvestigationReportDocument::getAllowedTypes()),
            'attachmentTypes' => $this->attachmentTypeFactory->makeAsArray(InvestigationReportAttachment::getAllowedTypes()),
            'attachmentLanguages' => $this->attachmentLanguageFactory->makeAsArray(),
            'grounds' => $this->groundViewFactory->makeAsArray(),
            'canDeleteAttachments' => $this->dossierWorkflowManager->isTransitionAllowed(
                $dossier,
                DossierStatusTransition::DELETE_ATTACHMENT,
            ),
        ]);
    }

    private function getForm(InvestigationReport $dossier): FormInterface
    {
        return $this->createForm(
            ContentFormType::class,
            $dossier,
            ['validation_groups' => [self::STEP_NAME->value]]
        );
    }
}
