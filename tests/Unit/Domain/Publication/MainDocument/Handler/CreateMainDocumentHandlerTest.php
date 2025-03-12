<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\MainDocument\Handler;

use App\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use App\Domain\Publication\Attachment\Enum\AttachmentType;
use App\Domain\Publication\Dossier\DossierRepository;
use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReport;
use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportMainDocument;
use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportMainDocumentRepository;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use App\Domain\Publication\FileInfo;
use App\Domain\Publication\MainDocument\Command\CreateMainDocumentCommand;
use App\Domain\Publication\MainDocument\Event\MainDocumentCreatedEvent;
use App\Domain\Publication\MainDocument\Handler\CreateMainDocumentHandler;
use App\Domain\Publication\MainDocument\MainDocumentAlreadyExistsException;
use App\Service\Uploader\UploaderService;
use App\Service\Uploader\UploadGroupId;
use Doctrine\ORM\EntityManagerInterface;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CreateMainDocumentHandlerTest extends MockeryTestCase
{
    private EntityManagerInterface&MockInterface $entityManager;
    private AnnualReportMainDocumentRepository&MockInterface $annualReportDocumentRepository;
    private MessageBusInterface&MockInterface $messageBus;
    private DossierWorkflowManager&MockInterface $dossierWorkflowManager;
    private CreateMainDocumentHandler $handler;
    private DossierRepository&MockInterface $dossierRepository;
    private UploaderService&MockInterface $uploaderService;
    private ValidatorInterface&MockInterface $validator;

    public function setUp(): void
    {
        $this->entityManager = \Mockery::mock(EntityManagerInterface::class);
        $this->annualReportDocumentRepository = \Mockery::mock(AnnualReportMainDocumentRepository::class);
        $this->dossierRepository = \Mockery::mock(DossierRepository::class);
        $this->messageBus = \Mockery::mock(MessageBusInterface::class);
        $this->dossierWorkflowManager = \Mockery::mock(DossierWorkflowManager::class);
        $this->uploaderService = \Mockery::mock(UploaderService::class);
        $this->validator = \Mockery::mock(ValidatorInterface::class);

        $this->handler = new CreateMainDocumentHandler(
            $this->messageBus,
            $this->dossierWorkflowManager,
            $this->entityManager,
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
        $dossier = \Mockery::mock(AnnualReport::class)->makePartial();
        $dossier->shouldReceive('getId')->andReturn($dossierUuid);
        $dossier->shouldReceive('getMainDocument')->andReturnNull();
        $dossier->shouldReceive('getMainDocumentEntityClass')->andReturn(AnnualReportMainDocument::class);

        $this->entityManager
            ->shouldReceive('getRepository')
            ->with(AnnualReportMainDocument::class)
            ->andReturn($this->annualReportDocumentRepository);

        $this->dossierRepository->shouldReceive('findOneByDossierId')->with($dossierUuid)->andReturn($dossier);

        $formalDate = new \DateTimeImmutable();
        $internalReference = 'foo bar';
        $attachmentType = AttachmentType::ANNUAL_REPORT;
        $language = AttachmentLanguage::DUTCH;
        $grounds = ['foo', 'bar'];

        $this->dossierWorkflowManager->expects('applyTransition')->with($dossier, DossierStatusTransition::UPDATE_MAIN_DOCUMENT);

        $command = new CreateMainDocumentCommand(
            $dossierUuid,
            $formalDate,
            $internalReference,
            $attachmentType,
            $language,
            $grounds,
            $uploadFileReference,
        );

        $fileInfo = \Mockery::mock(FileInfo::class);
        $fileInfo->shouldReceive('getName')->andReturn($uploadName);

        $mainDocument = \Mockery::mock(AnnualReportMainDocument::class);
        $mainDocument->expects('setInternalReference')->with($internalReference);
        $mainDocument->expects('setGrounds')->with($grounds);
        $mainDocument->shouldReceive('getFileInfo')->andReturn($fileInfo);
        $mainDocument->shouldReceive('getUploadGroupId')->andReturn(UploadGroupId::MAIN_DOCUMENTS);
        $mainDocument->shouldReceive('getId')->andReturn(Uuid::v6());
        $mainDocument->shouldReceive('getDossier')->andReturn($dossier);

        $this->annualReportDocumentRepository->expects('create')->with($dossier, $command)->andReturn($mainDocument);
        $this->annualReportDocumentRepository->expects('save')->with($mainDocument, true);

        $this->messageBus
            ->expects('dispatch')
            ->with(\Mockery::type(MainDocumentCreatedEvent::class))
            ->andReturns(new Envelope(new \stdClass()));

        $validatorList = \Mockery::mock(ConstraintViolationListInterface::class);
        $validatorList->expects('count')->andReturn(0);
        $this->validator->shouldReceive('validate')->andReturn($validatorList);

        $this->uploaderService
            ->expects('attachFileToEntity')
            ->with(
                $uploadFileReference,
                \Mockery::type(AnnualReportMainDocument::class),
                UploadGroupId::MAIN_DOCUMENTS,
            );

        self::assertEquals(
            $mainDocument,
            $this->handler->__invoke($command),
        );
    }

    public function testExceptionIsThrownWhenDocumentAlreadyExists(): void
    {
        $uploadFileReference = 'file-' . Uuid::v6();

        $formalDate = new \DateTimeImmutable();
        $internalReference = 'foo bar';
        $attachmentType = AttachmentType::ANNUAL_REPORT;
        $language = AttachmentLanguage::DUTCH;
        $grounds = ['foo', 'bar'];

        $annualReportDocument = \Mockery::mock(AnnualReportMainDocument::class);

        $dossierUuid = Uuid::v6();
        $dossier = \Mockery::mock(AnnualReport::class)->makePartial();
        $dossier->shouldReceive('getId')->andReturn($dossierUuid);
        $dossier->shouldReceive('getMainDocument')->andReturn($annualReportDocument);
        $dossier->shouldReceive('getMainDocumentEntityClass')->andReturn(AnnualReportMainDocument::class);

        $this->entityManager
            ->shouldReceive('getRepository')
            ->with(AnnualReportMainDocument::class)
            ->andReturn($this->annualReportDocumentRepository);

        $this->dossierRepository->shouldReceive('findOneByDossierId')->with($dossierUuid)->andReturn($dossier);

        $this->expectException(MainDocumentAlreadyExistsException::class);

        $this->handler->__invoke(
            new CreateMainDocumentCommand(
                $dossierUuid,
                $formalDate,
                $internalReference,
                $attachmentType,
                $language,
                $grounds,
                $uploadFileReference,
            )
        );
    }

    public function testExceptionIsThrownWhenValidatorHasErrors(): void
    {
        $uploadFileReference = 'file-' . Uuid::v6();
        $uploadName = 'test-123.pdf';

        $formalDate = new \DateTimeImmutable();
        $internalReference = 'foo bar';
        $attachmentType = AttachmentType::ANNUAL_PLAN;
        $language = AttachmentLanguage::DUTCH;
        $grounds = ['foo', 'bar'];

        $dossierUuid = Uuid::v6();
        $dossier = \Mockery::mock(AnnualReport::class)->makePartial();
        $dossier->shouldReceive('getId')->andReturn($dossierUuid);
        $dossier->shouldReceive('getMainDocument')->andReturnNull();
        $dossier->shouldReceive('getMainDocumentEntityClass')->andReturn(AnnualReportMainDocument::class);

        $this->entityManager
            ->shouldReceive('getRepository')
            ->with(AnnualReportMainDocument::class)
            ->andReturn($this->annualReportDocumentRepository);

        $fileInfo = \Mockery::mock(FileInfo::class);
        $fileInfo->shouldReceive('getName')->andReturn($uploadName);

        $mainDocument = \Mockery::mock(AnnualReportMainDocument::class);
        $mainDocument->expects('setInternalReference')->with($internalReference);
        $mainDocument->expects('setGrounds')->with($grounds);
        $mainDocument->shouldReceive('getFileInfo')->andReturn($fileInfo);
        $mainDocument->shouldReceive('getUploadGroupId')->andReturn(UploadGroupId::MAIN_DOCUMENTS);
        $mainDocument->shouldReceive('getId')->andReturn(Uuid::v6());
        $mainDocument->shouldReceive('getDossier')->andReturn($dossier);

        $command = new CreateMainDocumentCommand(
            $dossierUuid,
            $formalDate,
            $internalReference,
            $attachmentType,
            $language,
            $grounds,
            $uploadFileReference,
        );

        $this->annualReportDocumentRepository->expects('create')->with($dossier, $command)->andReturn($mainDocument);

        $this->dossierRepository->shouldReceive('findOneByDossierId')->with($dossierUuid)->andReturn($dossier);

        $validatorList = \Mockery::mock(ConstraintViolationListInterface::class);
        $validatorList->expects('count')->andReturn(2);
        $this->validator->shouldReceive('validate')->andReturn($validatorList);

        $this->dossierWorkflowManager->expects('applyTransition')->with($dossier, DossierStatusTransition::UPDATE_MAIN_DOCUMENT);

        $this->expectException(ValidationFailedException::class);

        $this->handler->__invoke($command);
    }
}
