<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\MainDocument\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Dossier\DossierRepository;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReport;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportMainDocument;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportMainDocumentRepository;
use Shared\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use Shared\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use Shared\Domain\Publication\FileInfo;
use Shared\Domain\Publication\MainDocument\Command\CreateMainDocumentCommand;
use Shared\Domain\Publication\MainDocument\Event\MainDocumentCreatedEvent;
use Shared\Domain\Publication\MainDocument\Handler\CreateMainDocumentHandler;
use Shared\Domain\Publication\MainDocument\MainDocumentAlreadyExistsException;
use Shared\Domain\Upload\Process\EntityUploadStorer;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\PlainDate;
use stdClass;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CreateMainDocumentHandlerTest extends UnitTestCase
{
    private EntityManagerInterface&MockInterface $entityManager;
    private AnnualReportMainDocumentRepository&MockInterface $annualReportDocumentRepository;
    private MessageBusInterface&MockInterface $messageBus;
    private DossierWorkflowManager&MockInterface $dossierWorkflowManager;
    private CreateMainDocumentHandler $handler;
    private DossierRepository&MockInterface $dossierRepository;
    private ValidatorInterface&MockInterface $validator;
    private EntityUploadStorer&MockInterface $uploadStorer;

    protected function setUp(): void
    {
        $this->entityManager = Mockery::mock(EntityManagerInterface::class);
        $this->annualReportDocumentRepository = Mockery::mock(AnnualReportMainDocumentRepository::class);
        $this->dossierRepository = Mockery::mock(DossierRepository::class);
        $this->messageBus = Mockery::mock(MessageBusInterface::class);
        $this->dossierWorkflowManager = Mockery::mock(DossierWorkflowManager::class);
        $this->validator = Mockery::mock(ValidatorInterface::class);
        $this->uploadStorer = Mockery::mock(EntityUploadStorer::class);

        $this->handler = new CreateMainDocumentHandler(
            $this->messageBus,
            $this->dossierWorkflowManager,
            $this->entityManager,
            $this->dossierRepository,
            $this->validator,
            $this->uploadStorer,
        );

        parent::setUp();
    }

    public function testEntityIsCreatedIfNoneExists(): void
    {
        $uploadFileReference = 'file-' . Uuid::v6();
        $uploadName = 'test-123.pdf';

        $dossierUuid = Uuid::v6();
        $dossier = Mockery::mock(AnnualReport::class)->makePartial();
        $dossier->expects('getId')->andReturn($dossierUuid);
        $dossier->expects('getMainDocument')->andReturnNull();
        $dossier->expects('getMainDocumentEntityClass')->andReturn(AnnualReportMainDocument::class);

        $this->entityManager
            ->expects('getRepository')
            ->with(AnnualReportMainDocument::class)
            ->andReturn($this->annualReportDocumentRepository);

        $this->dossierRepository->expects('findOneByDossierId')->with($dossierUuid)->andReturn($dossier);

        $formalDate = PlainDate::today();
        $internalReference = 'foo bar';
        $attachmentType = AttachmentType::ANNUAL_REPORT;
        $language = AttachmentLanguage::NLD;
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

        $fileInfo = Mockery::mock(FileInfo::class);
        $fileInfo->expects('getName')->andReturn($uploadName);

        $mainDocument = Mockery::mock(AnnualReportMainDocument::class);
        $mainDocument->expects('setInternalReference')->with($internalReference);
        $mainDocument->expects('setGrounds')->with($grounds);
        $mainDocument->expects('getFileInfo')->andReturn($fileInfo);
        $mainDocument->expects('getId')->andReturn(Uuid::v6());
        $mainDocument->expects('getDossier')->andReturn($dossier);

        $this->annualReportDocumentRepository->expects('create')->with($dossier, $command)->andReturn($mainDocument);
        $this->annualReportDocumentRepository->expects('save')->with($mainDocument, true);

        $this->messageBus
            ->expects('dispatch')
            ->with(Mockery::type(MainDocumentCreatedEvent::class))
            ->andReturns(new Envelope(new stdClass()));

        $validatorList = Mockery::mock(ConstraintViolationListInterface::class);
        $validatorList->expects('count')->andReturn(0);
        $this->validator->expects('validate')->andReturn($validatorList);

        $this->uploadStorer
            ->expects('storeUploadForEntityWithSourceTypeAndName')
            ->with(
                Mockery::type(AnnualReportMainDocument::class),
                $uploadFileReference,
            );

        self::assertEquals(
            $mainDocument,
            $this->handler->__invoke($command),
        );
    }

    public function testExceptionIsThrownWhenDocumentAlreadyExists(): void
    {
        $uploadFileReference = 'file-' . Uuid::v6();

        $formalDate = PlainDate::today();
        $internalReference = 'foo bar';
        $attachmentType = AttachmentType::ANNUAL_REPORT;
        $language = AttachmentLanguage::NLD;
        $grounds = ['foo', 'bar'];

        $annualReportDocument = Mockery::mock(AnnualReportMainDocument::class);

        $dossierUuid = Uuid::v6();
        $dossier = Mockery::mock(AnnualReport::class)->makePartial();
        $dossier->expects('getMainDocument')->andReturn($annualReportDocument);

        $this->dossierRepository->expects('findOneByDossierId')->with($dossierUuid)->andReturn($dossier);

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
            ),
        );
    }

    public function testExceptionIsThrownWhenValidatorHasErrors(): void
    {
        $uploadFileReference = 'file-' . Uuid::v6();

        $formalDate = PlainDate::today();
        $internalReference = 'foo bar';
        $attachmentType = AttachmentType::ANNUAL_PLAN;
        $language = AttachmentLanguage::NLD;
        $grounds = ['foo', 'bar'];

        $dossierUuid = Uuid::v6();
        $dossier = Mockery::mock(AnnualReport::class)->makePartial();
        $dossier->expects('getMainDocument')->andReturnNull();
        $dossier->expects('getMainDocumentEntityClass')->andReturn(AnnualReportMainDocument::class);

        $this->entityManager
            ->expects('getRepository')
            ->with(AnnualReportMainDocument::class)
            ->andReturn($this->annualReportDocumentRepository);

        $mainDocument = Mockery::mock(AnnualReportMainDocument::class);
        $mainDocument->expects('setInternalReference')->with($internalReference);
        $mainDocument->expects('setGrounds')->with($grounds);

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

        $this->dossierRepository->expects('findOneByDossierId')->with($dossierUuid)->andReturn($dossier);

        $validatorList = Mockery::mock(ConstraintViolationListInterface::class);
        $validatorList->expects('count')->andReturn(2);
        $this->validator->expects('validate')->andReturn($validatorList);

        $this->dossierWorkflowManager->expects('applyTransition')->with($dossier, DossierStatusTransition::UPDATE_MAIN_DOCUMENT);

        $this->expectException(ValidationFailedException::class);

        $this->handler->__invoke($command);
    }
}
