<?php

declare(strict_types=1);

namespace Shared\Controller\Admin\Dossier\DraftDecision;

use Huluti\BreadcrumbsBundle\Model\Breadcrumbs;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Attachment\Enum\AttachmentTypeBranch;
use Shared\Domain\Publication\Attachment\Enum\AttachmentTypeFactory;
use Shared\Domain\Publication\Dossier\DossierDispatcher;
use Shared\Domain\Publication\Dossier\Step\StepActionHelper;
use Shared\Domain\Publication\Dossier\Step\StepName;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecision;
use Shared\Form\Dossier\DraftDecision\ContentFormType;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @phpstan-import-type AttachmentTypeBranchArray from AttachmentTypeBranch
 * @phpstan-import-type AttachmentTypeArray from AttachmentType
 */
class ContentStepController extends AbstractController
{
    private const STEP_NAME = StepName::CONTENT;

    public function __construct(
        private readonly StepActionHelper $stepHelper,
        private readonly DossierDispatcher $dossierDispatcher,
        private readonly AttachmentTypeFactory $attachmentTypeFactory,
    ) {
    }

    #[Route(
        path: '/balie/dossier/draft-decision/content/concept/{documentPrefix}/{dossierNumber}',
        name: 'app_admin_dossier_draftdecision_content_concept',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('AuthMatrix.dossier.create', subject: 'dossier')]
    public function concept(
        #[MapEntity(mapping: ['documentPrefix' => 'documentPrefix', 'dossierNumber' => 'dossierNumber'])] DraftDecision $dossier,
        Request $request,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $breadcrumbs->addRouteItem(
            (string) $dossier->getTitle(),
            'app_admin_dossier',
            ['documentPrefix' => $dossier->getDocumentPrefix(), 'dossierNumber' => $dossier->getDossierNumber()],
        );
        $breadcrumbs->addItem('admin.dossiers.draft-decision.step.content');

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
            'admin/dossier/draft-decision/content/concept.html.twig',
            $this->stepHelper->getParamsBuilder($dossier)
                ->withMainDocumentParams($dossier)
                ->withAttachmentsParams($dossier)
                ->withForm($form)
                ->withWizardStatus($wizardStatus)
                ->withBreadCrumbs($breadcrumbs)
                ->withDepartments()
                ->with('requestForAdviceTypes', $this->getRequestForAdviceTypes())
                ->with('attachmentTypes', $this->getAttachmentTypes())
                ->getParams(),
        );
    }

    #[Route(
        path: '/balie/dossier/draft-decision/content/edit/{documentPrefix}/{dossierNumber}',
        name: 'app_admin_dossier_draftdecision_content_edit',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('AuthMatrix.dossier.update', subject: 'dossier')]
    public function edit(
        #[MapEntity(mapping: ['documentPrefix' => 'documentPrefix', 'dossierNumber' => 'dossierNumber'])] DraftDecision $dossier,
        Request $request,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $this->stepHelper->addDossierToBreadcrumbs($breadcrumbs, $dossier, 'admin.dossiers.draft-decision.step.content');

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
            'admin/dossier/draft-decision/content/edit.html.twig',
            $this->stepHelper->getParamsBuilder($dossier)
                ->withMainDocumentParams($dossier)
                ->withAttachmentsParams($dossier)
                ->withForm($form)
                ->withWizardStatus($wizardStatus)
                ->withBreadCrumbs($breadcrumbs)
                ->withDepartments()
                ->with('requestForAdviceTypes', $this->getRequestForAdviceTypes())
                ->with('attachmentTypes', $this->getAttachmentTypes())
                ->getParams(),
        );
    }

    private function getForm(DraftDecision $dossier): FormInterface
    {
        return $this->createForm(
            ContentFormType::class,
            $dossier,
            ['validation_groups' => [self::STEP_NAME->value]],
        );
    }

    /**
     * @return array<int,AttachmentTypeBranchArray|AttachmentTypeArray>
     */
    private function getAttachmentTypes(): array
    {
        return $this->attachmentTypeFactory->makeAsArray(
            AttachmentType::getCasesWithout(...$this->getRequestForAdviceAttachmentTypes()),
        );
    }

    /**
     * @return array<int,AttachmentTypeBranchArray|AttachmentTypeArray>
     */
    private function getRequestForAdviceTypes(): array
    {
        return $this->attachmentTypeFactory->makeAsArray($this->getRequestForAdviceAttachmentTypes());
    }

    /**
     * @return list<AttachmentType>
     */
    private function getRequestForAdviceAttachmentTypes(): array
    {
        return [
            AttachmentType::REQUEST_FOR_ADVICE,
            AttachmentType::POLICY_DOCUMENT,
        ];
    }
}
