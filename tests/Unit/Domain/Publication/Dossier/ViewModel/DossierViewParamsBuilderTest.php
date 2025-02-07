<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\ViewModel;

use App\Domain\Publication\Attachment\AttachmentLanguageFactory;
use App\Domain\Publication\Attachment\AttachmentTypeFactory;
use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantMainDocument;
use App\Domain\Publication\Dossier\ViewModel\DossierViewParamsBuilder;
use App\Domain\Publication\Dossier\ViewModel\GroundViewFactory;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
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
    private DepartmentRepository&MockInterface $departmentRepository;

    public function setUp(): void
    {
        $this->dossier = \Mockery::mock(Covenant::class);
        $this->workflowManager = \Mockery::mock(DossierWorkflowManager::class);
        $this->attachmentTypeFactory = \Mockery::mock(AttachmentTypeFactory::class);
        $this->attachmentLanguageFactory = \Mockery::mock(AttachmentLanguageFactory::class);
        $this->groundViewFactory = \Mockery::mock(GroundViewFactory::class);
        $this->departmentRepository = \Mockery::mock(DepartmentRepository::class);

        $this->builder = new DossierViewParamsBuilder(
            $this->workflowManager,
            $this->attachmentTypeFactory,
            $this->attachmentLanguageFactory,
            $this->groundViewFactory,
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
        $this->dossier->shouldReceive('getMainDocumentEntityClass')->andReturn(CovenantMainDocument::class);

        $this->workflowManager
            ->expects('isTransitionAllowed')
            ->with($this->dossier, DossierStatusTransition::DELETE_MAIN_DOCUMENT)
            ->andReturnTrue();

        $this->attachmentTypeFactory
            ->expects('makeAsArray')
            ->with(CovenantMainDocument::getAllowedTypes())
            ->andReturn(['types' => 'foo']);

        $this->attachmentLanguageFactory
            ->expects('makeAsArray')
            ->andReturn(['languages' => 'foo']);

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
                'documentMimeTypes' => CovenantMainDocument::getUploadGroupId()->getMimeTypes(),
                'documentExtensions' => CovenantMainDocument::getUploadGroupId()->getExtensions(),
                'documentTypeNames' => CovenantMainDocument::getUploadGroupId()->getFileTypeNames(),
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
                'attachmentMimeTypes' => CovenantMainDocument::getUploadGroupId()->getMimeTypes(),
                'attachmentExtensions' => CovenantMainDocument::getUploadGroupId()->getExtensions(),
                'attachmentTypeNames' => CovenantMainDocument::getUploadGroupId()->getFileTypeNames(),
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
