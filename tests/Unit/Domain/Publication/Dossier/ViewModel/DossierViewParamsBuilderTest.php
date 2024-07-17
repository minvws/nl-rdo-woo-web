<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\ViewModel;

use App\Domain\Publication\Attachment\AttachmentLanguageFactory;
use App\Domain\Publication\Attachment\AttachmentTypeFactory;
use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument;
use App\Domain\Publication\Dossier\ViewModel\DossierViewParamsBuilder;
use App\Domain\Publication\Dossier\ViewModel\GroundViewFactory;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use App\Domain\Upload\FileType\FileTypeHelper;
use App\Repository\DepartmentRepository;
use App\Service\DossierWizard\DossierWizardStatus;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\Form\FormInterface;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

final class DossierViewParamsBuilderTest extends MockeryTestCase
{
    private DossierViewParamsBuilder $builder;
    private Covenant&MockInterface $dossier;
    private DossierWorkflowManager&MockInterface $workflowManager;
    private AttachmentTypeFactory&MockInterface $attachmentTypeFactory;
    private AttachmentLanguageFactory&MockInterface $attachmentLanguageFactory;
    private GroundViewFactory&MockInterface $groundViewFactory;
    private FileTypeHelper&MockInterface $fileTypeHelper;
    private DepartmentRepository&MockInterface $departmentRepository;

    public function setUp(): void
    {
        $this->dossier = \Mockery::mock(Covenant::class);
        $this->workflowManager = \Mockery::mock(DossierWorkflowManager::class);
        $this->attachmentTypeFactory = \Mockery::mock(AttachmentTypeFactory::class);
        $this->attachmentLanguageFactory = \Mockery::mock(AttachmentLanguageFactory::class);
        $this->groundViewFactory = \Mockery::mock(GroundViewFactory::class);
        $this->fileTypeHelper = \Mockery::mock(FileTypeHelper::class);
        $this->departmentRepository = \Mockery::mock(DepartmentRepository::class);

        $this->builder = new DossierViewParamsBuilder(
            $this->workflowManager,
            $this->attachmentTypeFactory,
            $this->attachmentLanguageFactory,
            $this->groundViewFactory,
            $this->fileTypeHelper,
            $this->departmentRepository,
        );

        parent::setUp();
    }

    public function testForDossierResetsState(): void
    {
        $this->builder->forDossier($this->dossier)
            ->with('foo', 'bar');

        self::assertEquals(
            ['dossier' => $this->dossier, 'foo' => 'bar'],
            $this->builder->getParams(),
        );

        $this->builder->forDossier($this->dossier);

        self::assertEquals(
            ['dossier' => $this->dossier],
            $this->builder->getParams(),
        );
    }

    public function testWithMainDocumentParams(): void
    {
        $this->dossier->shouldReceive('getMainDocumentEntityClass')->andReturn(CovenantDocument::class);

        $this->workflowManager
            ->expects('isTransitionAllowed')
            ->with($this->dossier, DossierStatusTransition::DELETE_MAIN_DOCUMENT)
            ->andReturnTrue();

        $this->attachmentTypeFactory
            ->expects('makeAsArray')
            ->with(CovenantDocument::getAllowedTypes())
            ->andReturn(['types' => 'foo']);

        $this->attachmentLanguageFactory
            ->expects('makeAsArray')
            ->andReturn(['languages' => 'foo']);

        $this->fileTypeHelper
            ->expects('getMimeTypesByUploadGroup')
            ->with(CovenantDocument::getUploadGroupId())
            ->andReturn(['mimetypes' => 'foo']);

        $this->fileTypeHelper
            ->expects('getExtensionsByUploadGroup')
            ->with(CovenantDocument::getUploadGroupId())
            ->andReturn(['extensions' => 'foo']);

        $this->groundViewFactory
            ->expects('makeAsArray')
            ->andReturn(['grounds' => 'foo']);

        $this->builder->forDossier($this->dossier)->withMainDocumentParams($this->dossier);

        self::assertEquals(
            [
                'dossier' => $this->dossier,
                'canDeleteDocument' => true,
                'documentTypes' => ['types' => 'foo'],
                'documentLanguages' => ['languages' => 'foo'],
                'documentMimeTypes' => ['mimetypes' => 'foo'],
                'documentExtensions' => ['extensions' => 'foo'],
                'grounds' => ['grounds' => 'foo'],
            ],
            $this->builder->getParams(),
        );
    }

    public function testWithAttachmentParams(): void
    {
        $this->dossier->shouldReceive('getAttachmentEntityClass')->andReturn(CovenantAttachment::class);

        $this->workflowManager
            ->expects('isTransitionAllowed')
            ->with($this->dossier, DossierStatusTransition::DELETE_ATTACHMENT)
            ->andReturnTrue();

        $this->attachmentTypeFactory
            ->expects('makeAsArray')
            ->with(CovenantAttachment::getAllowedTypes())
            ->andReturn(['types' => 'foo']);

        $this->attachmentLanguageFactory
            ->expects('makeAsArray')
            ->andReturn(['languages' => 'foo']);

        $this->fileTypeHelper
            ->expects('getMimeTypesByUploadGroup')
            ->with(CovenantAttachment::getUploadGroupId())
            ->andReturn(['mimetypes' => 'foo']);

        $this->fileTypeHelper
            ->expects('getExtensionsByUploadGroup')
            ->with(CovenantAttachment::getUploadGroupId())
            ->andReturn(['extensions' => 'foo']);

        $this->groundViewFactory
            ->expects('makeAsArray')
            ->andReturn(['grounds' => 'foo']);

        $this->builder->forDossier($this->dossier)->withAttachmentsParams($this->dossier);

        self::assertEquals(
            [
                'dossier' => $this->dossier,
                'canDeleteAttachments' => true,
                'attachmentTypes' => ['types' => 'foo'],
                'attachmentLanguages' => ['languages' => 'foo'],
                'attachmentMimeTypes' => ['mimetypes' => 'foo'],
                'attachmentExtensions' => ['extensions' => 'foo'],
                'grounds' => ['grounds' => 'foo'],
            ],
            $this->builder->getParams(),
        );
    }

    public function testWithForm(): void
    {
        $form = \Mockery::mock(FormInterface::class);

        $this->builder->forDossier($this->dossier)->withForm($form);

        self::assertEquals(
            [
                'dossier' => $this->dossier,
                'form' => $form,
            ],
            $this->builder->getParams(),
        );
    }

    public function testWithWizardStatus(): void
    {
        $status = \Mockery::mock(DossierWizardStatus::class);

        $this->builder->forDossier($this->dossier)->withWizardStatus($status);

        self::assertEquals(
            [
                'dossier' => $this->dossier,
                'workflowStatus' => $status,
            ],
            $this->builder->getParams(),
        );
    }

    public function testWith(): void
    {
        $this->builder->forDossier($this->dossier)->with('foo', 'bar');

        self::assertEquals(
            [
                'dossier' => $this->dossier,
                'foo' => 'bar',
            ],
            $this->builder->getParams(),
        );
    }

    public function testWithBreadcrumbs(): void
    {
        $breadcrumbs = \Mockery::mock(Breadcrumbs::class);

        $this->builder->forDossier($this->dossier)->withBreadcrumbs($breadcrumbs);

        self::assertEquals(
            [
                'dossier' => $this->dossier,
                'breadcrumbs' => $breadcrumbs,
            ],
            $this->builder->getParams(),
        );
    }

    public function testWithDepartments(): void
    {
        $this->departmentRepository->expects('getNames')->andReturn(['foo', 'bar']);

        $this->builder->forDossier($this->dossier)->withDepartments();

        self::assertEquals(
            [
                'dossier' => $this->dossier,
                'departments' => ['foo', 'bar'],
            ],
            $this->builder->getParams(),
        );
    }
}
