<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\Covenant\Handler\CovenantDocument;

use App\Domain\Publication\Attachment\AttachmentLanguage;
use App\Domain\Publication\Dossier\Type\Covenant\Command\CreateCovenantDocumentCommand;
use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantDocumentRepository;
use App\Domain\Publication\Dossier\Type\Covenant\Event\CovenantDocumentUpdatedEvent;
use App\Domain\Publication\Dossier\Type\Covenant\Handler\CovenantDocument\CovenantDocumentAlreadyExistsException;
use App\Domain\Publication\Dossier\Type\Covenant\Handler\CovenantDocument\CreateCovenantDocumentHandler;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
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

class CreateCovenantDocumentHandlerTest extends MockeryTestCase
{
    private CovenantDocumentRepository&MockInterface $covenantDocumentRepository;
    private MessageBusInterface&MockInterface $messageBus;
    private DossierWorkflowManager&MockInterface $dossierWorkflowManager;
    private CreateCovenantDocumentHandler $handler;
    private MockInterface&CovenantRepository $dossierRepository;
    private MockInterface&UploaderService $uploaderService;
    private ValidatorInterface&MockInterface $validator;

    public function setUp(): void
    {
        $this->covenantDocumentRepository = \Mockery::mock(CovenantDocumentRepository::class);
        $this->dossierRepository = \Mockery::mock(CovenantRepository::class);
        $this->messageBus = \Mockery::mock(MessageBusInterface::class);
        $this->dossierWorkflowManager = \Mockery::mock(DossierWorkflowManager::class);
        $this->uploaderService = \Mockery::mock(UploaderService::class);
        $this->validator = \Mockery::mock(ValidatorInterface::class);

        $this->handler = new CreateCovenantDocumentHandler(
            $this->messageBus,
            $this->dossierWorkflowManager,
            $this->covenantDocumentRepository,
            $this->dossierRepository,
            $this->uploaderService,
            $this->validator,
        );

        parent::setUp();
    }

    public function testEntityIsCreatedIfNoneExists(): void
    {
        $uploadFileReference = 'file-' . Uuid::v6();
        $uploadName = 'test-123.pdf';

        $dossierUuid = Uuid::v6();
        $dossier = \Mockery::mock(Covenant::class);
        $dossier->shouldReceive('getId')->andReturn($dossierUuid);
        $dossier->shouldReceive('getDocument')->andReturnNull();

        $this->dossierRepository->shouldReceive('findOneByDossierId')->with($dossierUuid)->andReturn($dossier);

        $formalDate = new \DateTimeImmutable();
        $internalReference = 'foo bar';
        $language = AttachmentLanguage::DUTCH;
        $grounds = ['foo', 'bar'];

        $this->dossierWorkflowManager->expects('applyTransition')->with($dossier, DossierStatusTransition::UPDATE_CONTENT);

        $documentValidator = static function (CovenantDocument $document) use ($language, $grounds, $internalReference, $formalDate) {
            return $document->getLanguage() === $language
                && $document->getGrounds() === $grounds
                && $document->getInternalReference() === $internalReference
                && $document->getFormalDate() === $formalDate;
        };

        $this->covenantDocumentRepository->expects('save')->with(\Mockery::on($documentValidator), true);

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (CovenantDocumentUpdatedEvent $message) use ($documentValidator) {
                return $documentValidator($message->document);
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
            new CreateCovenantDocumentCommand(
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

    public function testExceptionIsThrownWhenDocumentAlreadyExists(): void
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

        $this->expectException(CovenantDocumentAlreadyExistsException::class);

        $this->handler->__invoke(
            new CreateCovenantDocumentCommand(
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

    public function testExceptionIsThrownWhenValidatorHasErrors(): void
    {
        $uploadFileReference = 'file-' . Uuid::v6();
        $uploadName = 'test-123.pdf';

        $formalDate = new \DateTimeImmutable();
        $internalReference = 'foo bar';
        $language = AttachmentLanguage::DUTCH;
        $grounds = ['foo', 'bar'];

        $dossierUuid = Uuid::v6();
        $dossier = \Mockery::mock(Covenant::class);
        $dossier->shouldReceive('getId')->andReturn($dossierUuid);
        $dossier->shouldReceive('getDocument')->andReturnNull();

        $this->dossierRepository->shouldReceive('findOneByDossierId')->with($dossierUuid)->andReturn($dossier);

        $validatorList = \Mockery::mock(ConstraintViolationListInterface::class);
        $validatorList->expects('count')->andReturn(2);
        $this->validator->shouldReceive('validate')->andReturn($validatorList);

        $this->dossierWorkflowManager->expects('applyTransition')->with($dossier, DossierStatusTransition::UPDATE_CONTENT);

        $this->expectException(ValidationFailedException::class);

        $this->handler->__invoke(
            new CreateCovenantDocumentCommand(
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
}
