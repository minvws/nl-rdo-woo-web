<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Uploads\Attachment;

use ApiPlatform\Metadata\Put;
use ApiPlatform\Validator\Exception\ValidationException;
use Mockery;
use Psr\Http\Message\StreamInterface;
use PublicationApi\Api\Dossier\Covenant\Uploads\Attachment\CovenantUploadAttachmentProcessor;
use PublicationApi\Api\DossierLookup;
use PublicationApi\Api\OrganisationLookup;
use PublicationApi\Api\Uploads\Attachment\UploadAttachmentHandler;
use PublicationApi\Api\Uploads\Attachment\UploadAttachmentRequestDto;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\Attachment\Repository\AttachmentRepository;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\Advice\AdviceAttachment;
use Shared\Domain\Publication\Dossier\Type\DossierRepositoryWithExternalId;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\ExternalId;
use stdClass;
use Symfony\Component\Uid\Uuid;

class UploadAttachmentProcessorTest extends UnitTestCase
{
    public function testProcessDelegatesToHandler(): void
    {
        $organisationId = Uuid::v6();
        $dossierExternalId = Mockery::mock(ExternalId::class);
        $attachmentExternalId = Mockery::mock(ExternalId::class);
        $content = Mockery::mock(StreamInterface::class);

        $uploadAttachmentRequestDto = new UploadAttachmentRequestDto($content, $organisationId, $dossierExternalId, $attachmentExternalId);

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

        $uploadAttachmentProcessor = new CovenantUploadAttachmentProcessor(
            $attachmentRepository,
            $dossierLookup,
            $organisationLookup,
            $uploadAttachmentHandler,
            $dossierRepository,
            AbstractAttachment::class,
        );

        $uploadAttachmentProcessor->process($uploadAttachmentRequestDto, new Put());
    }

    public function testProcessThrowsWhenAttachmentNotFound(): void
    {
        $organisationId = Uuid::v6();
        $dossierExternalId = Mockery::mock(ExternalId::class);
        $attachmentExternalId = Mockery::mock(ExternalId::class);

        $uploadAttachmentRequestDto = new UploadAttachmentRequestDto(
            Mockery::mock(StreamInterface::class),
            $organisationId,
            $dossierExternalId,
            $attachmentExternalId,
        );

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

        $uploadAttachmentProcessor = new CovenantUploadAttachmentProcessor(
            $attachmentRepository,
            $dossierLookup,
            $organisationLookup,
            $uploadAttachmentHandler,
            $dossierRepository,
            AbstractAttachment::class,
        );

        $this->expectException(ValidationException::class);

        $uploadAttachmentProcessor->process($uploadAttachmentRequestDto, new Put());
    }

    public function testProcessThrowsWhenAttachmentIsWrongType(): void
    {
        $organisationId = Uuid::v6();
        $dossierExternalId = Mockery::mock(ExternalId::class);
        $attachmentExternalId = Mockery::mock(ExternalId::class);

        $uploadAttachmentRequestDto = new UploadAttachmentRequestDto(
            Mockery::mock(StreamInterface::class),
            $organisationId,
            $dossierExternalId,
            $attachmentExternalId,
        );

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

        $uploadAttachmentProcessor = new CovenantUploadAttachmentProcessor(
            $attachmentRepository,
            $dossierLookup,
            $organisationLookup,
            $uploadAttachmentHandler,
            $dossierRepository,
            AdviceAttachment::class,
        );

        $this->expectException(ValidationException::class);

        $uploadAttachmentProcessor->process($uploadAttachmentRequestDto, new Put());
    }

    public function testProcessThrowsOnInvalidData(): void
    {
        $uploadAttachmentProcessor = new CovenantUploadAttachmentProcessor(
            Mockery::mock(AttachmentRepository::class),
            Mockery::mock(DossierLookup::class),
            Mockery::mock(OrganisationLookup::class),
            Mockery::mock(UploadAttachmentHandler::class),
            Mockery::mock(DossierRepositoryWithExternalId::class),
            AbstractAttachment::class,
        );

        $this->expectException(ValidationException::class);

        $uploadAttachmentProcessor->process(new stdClass(), new Put());
    }
}
