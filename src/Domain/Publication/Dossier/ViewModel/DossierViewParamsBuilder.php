<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\ViewModel;

use App\Domain\Publication\Attachment\AttachmentLanguageFactory;
use App\Domain\Publication\Attachment\AttachmentTypeFactory;
use App\Domain\Publication\Attachment\EntityWithAttachments;
use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use App\Domain\Publication\MainDocument\EntityWithMainDocument;
use App\Domain\Upload\FileType\FileTypeHelper;
use App\Repository\DepartmentRepository;
use App\Service\DossierWizard\DossierWizardStatus;
use Symfony\Component\Form\FormInterface;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
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
        private readonly FileTypeHelper $fileTypeHelper,
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
            $dossier->getMainDocumentEntityClass()::getAllowedTypes()
        );

        $this->params['documentLanguages'] = $this->attachmentLanguageFactory->makeAsArray();

        $this->params['documentMimeTypes'] = $this->fileTypeHelper->getMimeTypesByUploadGroup(
            $dossier->getMainDocumentEntityClass()::getUploadGroupId()
        );

        $this->params['documentExtensions'] = $this->fileTypeHelper->getExtensionsByUploadGroup(
            $dossier->getMainDocumentEntityClass()::getUploadGroupId()
        );

        $this->params['documentTypeNames'] = $this->fileTypeHelper->getTypeNamesByUploadGroup(
            $dossier->getMainDocumentEntityClass()::getUploadGroupId()
        );

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
            $dossier->getAttachmentEntityClass()::getAllowedTypes()
        );

        $this->params['attachmentMimeTypes'] = $this->fileTypeHelper->getMimeTypesByUploadGroup(
            $dossier->getAttachmentEntityClass()::getUploadGroupId()
        );

        $this->params['attachmentExtensions'] = $this->fileTypeHelper->getExtensionsByUploadGroup(
            $dossier->getAttachmentEntityClass()::getUploadGroupId()
        );

        $this->params['attachmentTypeNames'] = $this->fileTypeHelper->getTypeNamesByUploadGroup(
            $dossier->getAttachmentEntityClass()::getUploadGroupId()
        );

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