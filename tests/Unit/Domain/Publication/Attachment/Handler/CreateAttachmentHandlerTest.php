<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Attachment\Handler;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Attachment\AttachmentDispatcher;
use Shared\Domain\Publication\Attachment\Command\CreateAttachmentCommand;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Attachment\Handler\AttachmentEntityLoader;
use Shared\Domain\Publication\Attachment\Handler\CreateAttachmentHandler;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReport;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantAttachmentRepository;
use Shared\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use Shared\Domain\Upload\Process\EntityUploadStorer;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CreateAttachmentHandlerTest extends UnitTestCase
{
    private EntityManagerInterface&MockInterface $entityManager;
    private CreateAttachmentHandler $handler;
    private AttachmentEntityLoader&MockInterface $entityLoader;
    private AttachmentDispatcher&MockInterface $dispatcher;
    private ValidatorInterface&MockInterface $validator;
    private EntityUploadStorer&MockInterface $uploadStorer;

    protected function setUp(): void
    {
        $this->entityManager = Mockery::mock(EntityManagerInterface::class);
        $this->entityLoader = Mockery::mock(AttachmentEntityLoader::class);
        $this->dispatcher = Mockery::mock(AttachmentDispatcher::class);
        $this->validator = Mockery::mock(ValidatorInterface::class);
        $this->uploadStorer = Mockery::mock(EntityUploadStorer::class);

        $this->handler = new CreateAttachmentHandler(
            $this->entityManager,
            $this->validator,
            $this->entityLoader,
            $this->dispatcher,
            $this->uploadStorer,
        );

        parent::setUp();
    }

    public function testExceptionIsThrownWhenDossierValidationFails(): void
    {
        $dossierUuid = Uuid::v6();
        $dossier = Mockery::mock(AnnualReport::class);
        $dossier->shouldReceive('getId')->andReturn($dossierUuid);
        $dossier->shouldReceive('getAttachmentEntityClass')->andReturn(CovenantAttachment::class);

        $command = new CreateAttachmentCommand(
            $dossierUuid,
            formalDate: new DateTimeImmutable(),
            internalReference: $internalReference = 'foo',
            type: AttachmentType::ADVICE,
            language: AttachmentLanguage::DUTCH,
            grounds: $grounds = ['foo', 'bar'],
            uploadFileReference: 'bar',
        );

        $this->entityLoader
            ->expects('loadAndValidateDossier')
            ->with($dossierUuid, DossierStatusTransition::UPDATE_ATTACHMENT)
            ->andReturn($dossier);

        $attachment = Mockery::mock(CovenantAttachment::class);
        $attachment->expects('setInternalReference')->with($internalReference);
        $attachment->expects('setGrounds')->with($grounds);
        $attachment->shouldReceive('getDossier')->andReturn($dossier);

        $attachmentRepository = Mockery::mock(CovenantAttachmentRepository::class);
        $attachmentRepository->expects('create')->with($dossier, $command)->andReturn($attachment);

        $this->entityManager->expects('getRepository')
            ->with(CovenantAttachment::class)
            ->andReturn($attachmentRepository);

        $dossier->expects('addAttachment')->with($attachment);

        $this->validator->expects('validate')->with($attachment)->andReturn(new ConstraintViolationList());
        $this->validator->expects('validate')->with($dossier)->andReturn(new ConstraintViolationList([
            new ConstraintViolation('foo', null, [], null, null, 'foo'),
        ]));

        $this->expectException(ValidationFailedException::class);
        $this->handler->__invoke($command);
    }
}
