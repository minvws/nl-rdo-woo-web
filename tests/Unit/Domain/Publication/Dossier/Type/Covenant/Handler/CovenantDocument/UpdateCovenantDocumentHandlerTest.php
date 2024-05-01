<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\Covenant\Handler\CovenantDocument;

use App\Domain\Publication\Attachment\AttachmentLanguage;
use App\Domain\Publication\Dossier\Type\Covenant\Command\UpdateCovenantDocumentCommand;
use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantDocumentRepository;
use App\Domain\Publication\Dossier\Type\Covenant\Event\CovenantDocumentUpdatedEvent;
use App\Domain\Publication\Dossier\Type\Covenant\Handler\CovenantDocument\CovenantDocumentNotFoundException;
use App\Domain\Publication\Dossier\Type\Covenant\Handler\CovenantDocument\UpdateCovenantDocumentHandler;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use App\Entity\FileInfo;
use App\Repository\CovenantRepository;
use App\Service\Uploader\UploaderService;
use App\Service\Uploader\UploadGroupId;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UpdateCovenantDocumentHandlerTest extends MockeryTestCase
{
    private CovenantDocumentRepository&MockInterface $covenantDocumentRepository;
    private MessageBusInterface&MockInterface $messageBus;
    private DossierWorkflowManager&MockInterface $dossierWorkflowManager;
    private UpdateCovenantDocumentHandler $handler;
    private MockInterface&CovenantRepository $dossierRepository;
    private UploaderService&MockInterface $uploaderService;
    private ValidatorInterface&MockInterface $validator;

    public function setUp(): void
    {
        $this->covenantDocumentRepository = \Mockery::mock(CovenantDocumentRepository::class);
        $this->dossierRepository = \Mockery::mock(CovenantRepository::class);
        $this->messageBus = \Mockery::mock(MessageBusInterface::class);
        $this->dossierWorkflowManager = \Mockery::mock(DossierWorkflowManager::class);
        $this->uploaderService = \Mockery::mock(UploaderService::class);
        $this->validator = \Mockery::mock(ValidatorInterface::class);

        $this->handler = new UpdateCovenantDocumentHandler(
            $this->messageBus,
            $this->dossierWorkflowManager,
            $this->covenantDocumentRepository,
            $this->dossierRepository,
            $this->uploaderService,
            $this->validator,
        );

        parent::setUp();
    }

    public function testEntityIsUpdated(): void
    {
        $uploadFileReference = 'file-' . Uuid::v6();
        $uploadName = 'test-123.pdf';

        $formalDate = new \DateTimeImmutable();
        $internalReference = 'foo bar';
        $language = AttachmentLanguage::DUTCH;
        $grounds = ['foo', 'bar'];

        $covenantDocument = \Mockery::mock(CovenantDocument::class);
        $covenantDocument->expects('setFormalDate')->with($formalDate);
        $covenantDocument->expects('setInternalReference')->with($internalReference);
        $covenantDocument->expects('setLanguage')->with($language);
        $covenantDocument->expects('setGrounds')->with($grounds);
        $covenantDocument->expects('getFileInfo')->andReturn(new FileInfo());
        $covenantDocument->expects('getUploadGroupId')->andReturn(UploadGroupId::COVENANT_DOCUMENTS);

        $dossierUuid = Uuid::v6();
        $dossier = \Mockery::mock(Covenant::class);
        $dossier->shouldReceive('getId')->andReturn($dossierUuid);
        $dossier->shouldReceive('getDocument')->andReturn($covenantDocument);

        $this->dossierRepository->shouldReceive('findOneByDossierId')->with($dossierUuid)->andReturn($dossier);

        $this->dossierWorkflowManager->expects('applyTransition')->with($dossier, DossierStatusTransition::UPDATE_CONTENT);

        $this->covenantDocumentRepository->expects('findOneByDossierId')->with($dossierUuid)->andReturn($covenantDocument);
        $this->covenantDocumentRepository->expects('save')->with($covenantDocument, true);

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (CovenantDocumentUpdatedEvent $message) use ($covenantDocument) {
                return $message->document === $covenantDocument;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $validatorList = \Mockery::mock(ConstraintViolationListInterface::class);
        $validatorList->expects('count')->andReturn(0);
        $this->validator->shouldReceive('validate')->andReturn($validatorList);

        $this->uploaderService
            ->expects('attachFileToEntity')
            ->with(
                $uploadFileReference,
                \Mockery::type(CovenantDocument::class),
                UploadGroupId::COVENANT_DOCUMENTS,
            );

        $this->handler->__invoke(
            new UpdateCovenantDocumentCommand(
                $dossierUuid,
                $formalDate,
                $internalReference,
                $language,
                $grounds,
                $uploadFileReference,
                $uploadName,
            )
        );
    }

    public function testExceptionIsThrownWhenDocumentDoesntExists(): void
    {
        $uploadFileReference = 'file-' . Uuid::v6();
        $uploadName = 'test-123.pdf';

        $formalDate = new \DateTimeImmutable();
        $internalReference = 'foo bar';
        $language = AttachmentLanguage::DUTCH;
        $grounds = ['foo', 'bar'];

        $covenantDocument = \Mockery::mock(CovenantDocument::class);

        $dossierUuid = Uuid::v6();
        $dossier = \Mockery::mock(Covenant::class);
        $dossier->shouldReceive('getId')->andReturn($dossierUuid);
        $dossier->shouldReceive('getDocument')->andReturn($covenantDocument);

        $this->dossierRepository->shouldReceive('findOneByDossierId')->with($dossierUuid)->andReturn($dossier);

        $this->covenantDocumentRepository->expects('findOneByDossierId')->with($dossierUuid)->andReturnNull();

        $this->expectException(CovenantDocumentNotFoundException::class);

        $this->handler->__invoke(
            new UpdateCovenantDocumentCommand(
                $dossierUuid,
                $formalDate,
                $internalReference,
                $language,
                $grounds,
                $uploadFileReference,
                $uploadName,
            )
        );
    }

    public function testExceptionIsThrownWhenValidationFails(): void
    {
        $formalDate = new \DateTimeImmutable();
        $internalReference = 'foo bar';
        $language = AttachmentLanguage::DUTCH;
        $grounds = ['foo', 'bar'];

        $covenantDocument = \Mockery::mock(CovenantDocument::class);
        $covenantDocument->expects('setFormalDate')->with($formalDate);
        $covenantDocument->expects('setInternalReference')->with($internalReference);
        $covenantDocument->expects('setLanguage')->with($language);
        $covenantDocument->expects('setGrounds')->with($grounds);

        $dossierUuid = Uuid::v6();
        $dossier = \Mockery::mock(Covenant::class);
        $dossier->shouldReceive('getId')->andReturn($dossierUuid);
        $dossier->shouldReceive('getDocument')->andReturn($covenantDocument);

        $this->dossierRepository->shouldReceive('findOneByDossierId')->with($dossierUuid)->andReturn($dossier);

        $this->dossierWorkflowManager->expects('applyTransition')->with($dossier, DossierStatusTransition::UPDATE_CONTENT);

        $this->covenantDocumentRepository->expects('findOneByDossierId')->with($dossierUuid)->andReturn($covenantDocument);

        $validatorList = \Mockery::mock(ConstraintViolationListInterface::class);
        $validatorList->expects('count')->andReturn(3);
        $this->validator->shouldReceive('validate')->andReturn($validatorList);

        $this->expectException(ValidationFailedException::class);

        $this->handler->__invoke(
            new UpdateCovenantDocumentCommand(
                $dossierUuid,
                $formalDate,
                $internalReference,
                $language,
                $grounds,
                null,
                null,
            )
        );
    }
}
