<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Publication\Uploads\Attachment;

use ApiPlatform\Validator\Exception\ValidationException;
use Mockery;
use Psr\Http\Message\StreamInterface;
use PublicationApi\Api\Publication\DossierLookup;
use PublicationApi\Api\Publication\OrganisationLookup;
use PublicationApi\Api\Publication\Uploads\Attachment\UploadAttachmentHandler;
use PublicationApi\Api\Publication\Uploads\Attachment\UploadAttachmentProcessor;
use PublicationApi\Api\Publication\Uploads\Attachment\UploadAttachmentRequestInterface;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\Attachment\Repository\AttachmentRepository;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\Advice\AdviceAttachment;
use Shared\Domain\Publication\Dossier\Type\DossierRepositoryWithExternalId;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\ExternalId;

class UploadAttachmentProcessorTest extends UnitTestCase
{
    public function testProcessDelegatesToHandler(): void
    {
        $organisationId = $this->getFaker()->uuid();
        $dossierExternalId = Mockery::mock(ExternalId::class);
        $attachmentExternalId = Mockery::mock(ExternalId::class);
        $content = Mockery::mock(StreamInterface::class);

        $request = Mockery::mock(UploadAttachmentRequestInterface::class);
        $request->expects('getOrganisationId')->andReturn($organisationId);
        $request->expects('getDossierExternalId')->andReturn($dossierExternalId);
        $request->expects('getAttachmentExternalId')->andReturn($attachmentExternalId);
        $request->expects('getContent')->andReturn($content);

        $organisation = Mockery::mock(Organisation::class);
        $organisationLookup = Mockery::mock(OrganisationLookup::class);
        $organisationLookup
            ->expects('find')
            ->with($organisationId)
            ->andReturn($organisation);

        $dossierRepository = Mockery::mock(DossierRepositoryWithExternalId::class);
        $dossier = Mockery::mock(AbstractDossier::class);
        $dossierLookup = Mockery::mock(DossierLookup::class);
        $dossierLookup
            ->expects('find')
            ->with($dossierRepository, $organisation, $dossierExternalId)
            ->andReturn($dossier);

        $attachment = Mockery::mock(AbstractAttachment::class);
        $attachmentRepository = Mockery::mock(AttachmentRepository::class);
        $attachmentRepository
            ->expects('findByDossierAndExternalId')
            ->with($dossier, $attachmentExternalId)
            ->andReturn($attachment);

        $uploadAttachmentHandler = Mockery::mock(UploadAttachmentHandler::class);
        $uploadAttachmentHandler->expects('handle')->with($dossier, $attachment, $content);

        $processor = new UploadAttachmentProcessor(
            $attachmentRepository,
            $dossierLookup,
            $organisationLookup,
            $uploadAttachmentHandler,
        );

        $processor->process($request, $dossierRepository, AbstractAttachment::class);
    }

    public function testProcessThrowsWhenAttachmentNotFound(): void
    {
        $organisationId = $this->getFaker()->uuid();
        $dossierExternalId = Mockery::mock(ExternalId::class);
        $attachmentExternalId = Mockery::mock(ExternalId::class);

        $request = Mockery::mock(UploadAttachmentRequestInterface::class);
        $request->expects('getOrganisationId')->andReturn($organisationId);
        $request->expects('getDossierExternalId')->andReturn($dossierExternalId);
        $request->expects('getAttachmentExternalId')->andReturn($attachmentExternalId);

        $organisation = Mockery::mock(Organisation::class);
        $organisationLookup = Mockery::mock(OrganisationLookup::class);
        $organisationLookup
            ->expects('find')
            ->with($organisationId)
            ->andReturn($organisation);

        $dossierRepository = Mockery::mock(DossierRepositoryWithExternalId::class);
        $dossier = Mockery::mock(AbstractDossier::class);
        $dossierLookup = Mockery::mock(DossierLookup::class);
        $dossierLookup
            ->expects('find')
            ->with($dossierRepository, $organisation, $dossierExternalId)
            ->andReturn($dossier);

        $attachmentRepository = Mockery::mock(AttachmentRepository::class);
        $attachmentRepository
            ->expects('findByDossierAndExternalId')
            ->with($dossier, $attachmentExternalId)
            ->andReturnNull();

        $uploadAttachmentHandler = Mockery::mock(UploadAttachmentHandler::class);

        $processor = new UploadAttachmentProcessor(
            $attachmentRepository,
            $dossierLookup,
            $organisationLookup,
            $uploadAttachmentHandler,
        );

        $this->expectException(ValidationException::class);

        $processor->process($request, $dossierRepository, AbstractAttachment::class);
    }

    public function testProcessThrowsWhenAttachmentIsWrongType(): void
    {
        $organisationId = $this->getFaker()->uuid();
        $dossierExternalId = Mockery::mock(ExternalId::class);
        $attachmentExternalId = Mockery::mock(ExternalId::class);

        $request = Mockery::mock(UploadAttachmentRequestInterface::class);
        $request->expects('getOrganisationId')->andReturn($organisationId);
        $request->expects('getDossierExternalId')->andReturn($dossierExternalId);
        $request->expects('getAttachmentExternalId')->andReturn($attachmentExternalId);

        $organisation = Mockery::mock(Organisation::class);
        $organisationLookup = Mockery::mock(OrganisationLookup::class);
        $organisationLookup
            ->expects('find')
            ->with($organisationId)
            ->andReturn($organisation);

        $dossierRepository = Mockery::mock(DossierRepositoryWithExternalId::class);
        $dossier = Mockery::mock(AbstractDossier::class);
        $dossierLookup = Mockery::mock(DossierLookup::class);
        $dossierLookup
            ->expects('find')
            ->with($dossierRepository, $organisation, $dossierExternalId)
            ->andReturn($dossier);

        $attachment = Mockery::mock(AbstractAttachment::class);
        $attachmentRepository = Mockery::mock(AttachmentRepository::class);
        $attachmentRepository
            ->expects('findByDossierAndExternalId')
            ->with($dossier, $attachmentExternalId)
            ->andReturn($attachment);

        $uploadAttachmentHandler = Mockery::mock(UploadAttachmentHandler::class);

        $processor = new UploadAttachmentProcessor(
            $attachmentRepository,
            $dossierLookup,
            $organisationLookup,
            $uploadAttachmentHandler,
        );

        $this->expectException(ValidationException::class);

        $processor->process($request, $dossierRepository, AdviceAttachment::class);
    }
}
