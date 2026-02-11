<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\ViewModel;

use Shared\Domain\Department\DepartmentRepository;
use Shared\Domain\Publication\Attachment\Entity\EntityWithAttachments;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguageFactory;
use Shared\Domain\Publication\Attachment\Enum\AttachmentTypeFactory;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use Shared\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use Shared\Domain\Publication\MainDocument\EntityWithMainDocument;
use Shared\Service\DossierWizard\DossierWizardStatus;
use Symfony\Component\Form\FormInterface;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

class DossierViewParamsBuilder
{
    /**
     * @var array<string,mixed>
     */
    private array $params;

    public function __construct(
        private readonly DossierWorkflowManager $dossierWorkflowManager,
        private readonly AttachmentTypeFactory $attachmentTypeFactory,
        private readonly AttachmentLanguageFactory $attachmentLanguageFactory,
        private readonly GroundViewFactory $groundViewFactory,
        private readonly DepartmentRepository $departmentRepository,
    ) {
    }

    public function forDossier(AbstractDossier $dossier): self
    {
        $this->params = ['dossier' => $dossier];

        return $this;
    }

    public function withMainDocumentParams(AbstractDossier&EntityWithMainDocument $dossier): self
    {
        $this->params['canDeleteDocument'] = $this->dossierWorkflowManager->isTransitionAllowed(
            $dossier,
            DossierStatusTransition::DELETE_MAIN_DOCUMENT,
        );

        $this->params['documentTypes'] = $this->attachmentTypeFactory->makeAsArray(
            $dossier->getMainDocumentEntityClass()::getAllowedTypes(),
        );

        $this->params['documentLanguages'] = $this->attachmentLanguageFactory->makeAsArray();
        $this->params['documentFileLimits'] = $dossier->getMainDocumentEntityClass()::getUploadGroupId()->getFileLimits();
        $this->params['grounds'] = $this->groundViewFactory->makeAsArray();

        return $this;
    }

    public function withAttachmentsParams(AbstractDossier&EntityWithAttachments $dossier): self
    {
        $this->params['canDeleteAttachments'] = $this->dossierWorkflowManager->isTransitionAllowed(
            $dossier,
            DossierStatusTransition::DELETE_ATTACHMENT,
        );

        $this->params['attachmentTypes'] = $this->attachmentTypeFactory->makeAsArray(
            $dossier->getAttachmentEntityClass()::getAllowedTypes(),
        );

        $this->params['attachmentFileLimits'] = $dossier->getAttachmentEntityClass()::getUploadGroupId()->getFileLimits();
        $this->params['attachmentLanguages'] = $this->attachmentLanguageFactory->makeAsArray();
        $this->params['grounds'] = $this->groundViewFactory->makeAsArray();

        return $this;
    }

    public function withForm(FormInterface $form): self
    {
        return $this->with('form', $form);
    }

    public function withWizardStatus(DossierWizardStatus $status): self
    {
        return $this->with('workflowStatus', $status);
    }

    public function with(string $key, mixed $value): self
    {
        $this->params[$key] = $value;

        return $this;
    }

    public function withBreadcrumbs(Breadcrumbs $breadcrumbs): self
    {
        return $this->with('breadcrumbs', $breadcrumbs);
    }

    public function withDepartments(): self
    {
        return $this->with('departments', $this->departmentRepository->getNames());
    }

    /**
     * @return array<string,mixed>
     */
    public function getParams(): array
    {
        return $this->params;
    }
}
