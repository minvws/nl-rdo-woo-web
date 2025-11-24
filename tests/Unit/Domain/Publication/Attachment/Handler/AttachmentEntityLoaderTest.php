<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Attachment\Handler;

use Doctrine\ORM\NoResultException;
use Mockery\MockInterface;
use Shared\Domain\Publication\Attachment\Exception\AttachmentNotFoundException;
use Shared\Domain\Publication\Attachment\Handler\AttachmentEntityLoader;
use Shared\Domain\Publication\Attachment\Repository\AttachmentRepository;
use Shared\Domain\Publication\Dossier\DossierRepository;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use Shared\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use Shared\Domain\Publication\Dossier\Workflow\DossierWorkflowException;
use Shared\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

class AttachmentEntityLoaderTest extends UnitTestCase
{
    private DossierWorkflowManager&MockInterface $workflowManager;
    private AttachmentRepository&MockInterface $attachmentRepository;
    private DossierRepository&MockInterface $dossierRepository;
    private AttachmentEntityLoader $loader;

    protected function setUp(): void
    {
        $this->workflowManager = \Mockery::mock(DossierWorkflowManager::class);
        $this->attachmentRepository = \Mockery::mock(AttachmentRepository::class);
        $this->dossierRepository = \Mockery::mock(DossierRepository::class);

        $this->loader = new AttachmentEntityLoader(
            $this->workflowManager,
            $this->attachmentRepository,
            $this->dossierRepository,
        );
    }

    public function testLoadAndValidateDossierSuccessful(): void
    {
        $dossierId = Uuid::v6();
        $dossier = \Mockery::mock(Covenant::class);
        $transition = DossierStatusTransition::UPDATE_ATTACHMENT;

        $this->dossierRepository
            ->expects('findOneByDossierId')
            ->with($dossierId)
            ->andReturn($dossier);

        $this->workflowManager
            ->expects('applyTransition')
            ->with($dossier, $transition);

        $result = $this->loader->loadAndValidateDossier($dossierId, $transition);

        self::assertSame($dossier, $result);
    }

    public function testLoadAndValidateDossierThrowsExceptionWhenTransitionCannotBeApplied(): void
    {
        $dossierId = Uuid::v6();
        $dossier = \Mockery::mock(Covenant::class);
        $transition = DossierStatusTransition::UPDATE_ATTACHMENT;

        $this->dossierRepository->expects('findOneByDossierId')->with($dossierId)->andReturn($dossier);

        $this->workflowManager
            ->expects('applyTransition')
            ->with($dossier, $transition)
            ->andThrow(new DossierWorkflowException());

        $this->expectException(DossierWorkflowException::class);
        $this->loader->loadAndValidateDossier($dossierId, $transition);
    }

    public function testLoadAndValidateDossierThrowsExceptionWhenDossierIsNotFound(): void
    {
        $dossierId = Uuid::v6();
        $transition = DossierStatusTransition::UPDATE_ATTACHMENT;

        $this->dossierRepository
            ->expects('findOneByDossierId')
            ->with($dossierId)
            ->andThrow(new NoResultException());

        $this->expectException(NoResultException::class);
        $this->loader->loadAndValidateDossier($dossierId, $transition);
    }

    public function testLoadAndValidateAttachmentSuccessful(): void
    {
        $dossierId = Uuid::v6();
        $dossier = \Mockery::mock(Covenant::class);
        $dossier->shouldReceive('getId')->andReturn($dossierId);

        $attachmentId = Uuid::v6();
        $attachment = \Mockery::mock(CovenantAttachment::class);

        $transition = DossierStatusTransition::UPDATE_ATTACHMENT;

        $this->dossierRepository
            ->expects('findOneByDossierId')
            ->with($dossierId)
            ->andReturn($dossier);

        $this->workflowManager
            ->expects('applyTransition')
            ->with($dossier, $transition);

        $this->attachmentRepository
            ->expects('findOneForDossier')
            ->with($dossierId, $attachmentId)
            ->andReturn($attachment);

        $result = $this->loader->loadAndValidateAttachment($dossierId, $attachmentId, $transition);

        self::assertSame($attachment, $result);
    }

    public function testLoadAndValidateAttachmentThrowsExceptionWhenAttachmentIsNotFound(): void
    {
        $dossierId = Uuid::v6();
        $dossier = \Mockery::mock(Covenant::class);
        $dossier->shouldReceive('getId')->andReturn($dossierId);

        $attachmentId = Uuid::v6();

        $transition = DossierStatusTransition::UPDATE_ATTACHMENT;

        $this->dossierRepository
            ->expects('findOneByDossierId')
            ->with($dossierId)
            ->andReturn($dossier);

        $this->attachmentRepository
            ->expects('findOneForDossier')
            ->with($dossierId, $attachmentId)
            ->andThrow(NoResultException::class);

        $this->expectException(AttachmentNotFoundException::class);
        $this->loader->loadAndValidateAttachment($dossierId, $attachmentId, $transition);
    }

    public function testLoadAndValidateAttachmentThrowsExceptionWhenTransitionCannotBeApplied(): void
    {
        $dossierId = Uuid::v6();
        $dossier = \Mockery::mock(Covenant::class);
        $dossier->shouldReceive('getId')->andReturn($dossierId);

        $attachmentId = Uuid::v6();
        $attachment = \Mockery::mock(CovenantAttachment::class);

        $transition = DossierStatusTransition::UPDATE_ATTACHMENT;

        $this->dossierRepository
            ->expects('findOneByDossierId')
            ->with($dossierId)
            ->andReturn($dossier);

        $this->attachmentRepository
            ->expects('findOneForDossier')
            ->with($dossierId, $attachmentId)
            ->andReturn($attachment);

        $this->workflowManager
            ->expects('applyTransition')
            ->with($dossier, $transition)
            ->andThrow(new DossierWorkflowException());

        $this->expectException(DossierWorkflowException::class);
        $this->loader->loadAndValidateAttachment($dossierId, $attachmentId, $transition);
    }

    public function testLoadAttachmentSuccessful(): void
    {
        $dossierId = Uuid::v6();
        $dossier = \Mockery::mock(Covenant::class);
        $dossier->shouldReceive('getId')->andReturn($dossierId);

        $attachmentId = Uuid::v6();
        $attachment = \Mockery::mock(CovenantAttachment::class);

        $this->dossierRepository
            ->expects('findOneByDossierId')
            ->with($dossierId)
            ->andReturn($dossier);

        $this->attachmentRepository
            ->expects('findOneForDossier')
            ->with($dossierId, $attachmentId)
            ->andReturn($attachment);

        $result = $this->loader->loadAttachment($dossierId, $attachmentId);

        self::assertSame($attachment, $result);
    }

    public function testLoadAttachmentThrowsExceptionWhenAttachmentIsNotFound(): void
    {
        $dossierId = Uuid::v6();
        $dossier = \Mockery::mock(Covenant::class);
        $dossier->shouldReceive('getId')->andReturn($dossierId);

        $attachmentId = Uuid::v6();

        $this->dossierRepository
            ->expects('findOneByDossierId')
            ->with($dossierId)
            ->andReturn($dossier);

        $this->attachmentRepository
            ->expects('findOneForDossier')
            ->with($dossierId, $attachmentId)
            ->andThrow(NoResultException::class);

        $this->expectException(AttachmentNotFoundException::class);
        $this->loader->loadAttachment($dossierId, $attachmentId);
    }
}
