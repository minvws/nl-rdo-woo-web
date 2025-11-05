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
use App\Domain\Publication\MainDocument\Command\UpdateMainDocumentCommand;
use App\Domain\Publication\MainDocument\Event\MainDocumentUpdatedEvent;
use App\Domain\Publication\MainDocument\Handler\UpdateMainDocumentHandler;
use App\Domain\Publication\MainDocument\MainDocumentNotFoundException;
use App\Domain\Upload\Process\EntityUploadStorer;
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

class UpdateMainDocumentHandlerTest extends MockeryTestCase
{
    private EntityManagerInterface&MockInterface $entityManager;
    private AnnualReportMainDocumentRepository&MockInterface $annualReportDocumentRepository;
    private MessageBusInterface&MockInterface $messageBus;
    private DossierWorkflowManager&MockInterface $dossierWorkflowManager;
    private UpdateMainDocumentHandler $handler;
    private DossierRepository&MockInterface $dossierRepository;
    private ValidatorInterface&MockInterface $validator;
    private EntityUploadStorer&MockInterface $uploadStorer;

    protected function setUp(): void
    {
        $this->entityManager = \Mockery::mock(EntityManagerInterface::class);
        $this->annualReportDocumentRepository = \Mockery::mock(AnnualReportMainDocumentRepository::class);
        $this->dossierRepository = \Mockery::mock(DossierRepository::class);
        $this->messageBus = \Mockery::mock(MessageBusInterface::class);
        $this->dossierWorkflowManager = \Mockery::mock(DossierWorkflowManager::class);
        $this->validator = \Mockery::mock(ValidatorInterface::class);
        $this->uploadStorer = \Mockery::mock(EntityUploadStorer::class);

        $this->handler = new UpdateMainDocumentHandler(
            $this->messageBus,
            $this->dossierWorkflowManager,
            $this->entityManager,
            $this->dossierRepository,
            $this->validator,
            $this->uploadStorer,
        );

        parent::setUp();
    }

    public function testEntityIsUpdated(): void
    {
        $uploadFileReference = 'file-' . Uuid::v6();

        $formalDate = new \DateTimeImmutable();
        $internalReference = 'foo bar';
        $attachmentType = AttachmentType::ANNUAL_PLAN;
        $language = AttachmentLanguage::DUTCH;
        $grounds = ['foo', 'bar'];

        $dossierUuid = Uuid::v6();
        $dossier = \Mockery::mock(AnnualReport::class)->makePartial();
        $dossier->shouldReceive('getId')->andReturn($dossierUuid);
        $dossier->shouldReceive('getMainDocumentEntityClass')->andReturn(AnnualReportMainDocument::class);

        $this->entityManager
            ->shouldReceive('getRepository')
            ->with(AnnualReportMainDocument::class)
            ->andReturn($this->annualReportDocumentRepository);

        $annualReportDocument = \Mockery::mock(AnnualReportMainDocument::class);
        $annualReportDocument->shouldReceive('getId')->andReturn(Uuid::v6());
        $annualReportDocument->shouldReceive('getDossier')->andReturn($dossier);
        $annualReportDocument->shouldReceive('setFormalDate')->with($formalDate);
        $annualReportDocument->shouldReceive('setInternalReference')->with($internalReference);
        $annualReportDocument->shouldReceive('setType')->with($attachmentType);
        $annualReportDocument->shouldReceive('setLanguage')->with($language);
        $annualReportDocument->shouldReceive('setGrounds')->with($grounds);
        $annualReportDocument->shouldReceive('getFileInfo')->andReturn(new FileInfo());
        $annualReportDocument->shouldReceive('getUploadGroupId')->andReturn(UploadGroupId::MAIN_DOCUMENTS);

        $dossier->shouldReceive('getMainDocument')->andReturn($annualReportDocument);

        $this->dossierRepository->shouldReceive('findOneByDossierId')->with($dossierUuid)->andReturn($dossier);

        $this->dossierWorkflowManager->expects('applyTransition')->with($dossier, DossierStatusTransition::UPDATE_MAIN_DOCUMENT);

        $this->annualReportDocumentRepository->expects('findOneByDossierId')->with($dossierUuid)->andReturn($annualReportDocument);
        $this->annualReportDocumentRepository->expects('save')->with($annualReportDocument, true);

        $this->messageBus
            ->expects('dispatch')
            ->with(\Mockery::type(MainDocumentUpdatedEvent::class))
            ->andReturns(new Envelope(new \stdClass()));

        $validatorList = \Mockery::mock(ConstraintViolationListInterface::class);
        $validatorList->expects('count')->andReturn(0);
        $this->validator->shouldReceive('validate')->andReturn($validatorList);

        $this->uploadStorer
            ->expects('storeUploadForEntityWithSourceTypeAndName')
            ->with(
                \Mockery::type(AnnualReportMainDocument::class),
                $uploadFileReference,
            );

        $this->handler->__invoke(
            new UpdateMainDocumentCommand(
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

    protected function mockeryTestTearDown(): void
    {
        parent::mockeryTestTearDown(); // TODO: Change the autogenerated stub
    }

    public function testExceptionIsThrownWhenDocumentDoesntExists(): void
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

        $this->annualReportDocumentRepository->expects('findOneByDossierId')->with($dossierUuid)->andReturnNull();

        $this->expectException(MainDocumentNotFoundException::class);

        $this->handler->__invoke(
            new UpdateMainDocumentCommand(
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

    public function testExceptionIsThrownWhenValidationFails(): void
    {
        $formalDate = new \DateTimeImmutable();
        $internalReference = 'foo bar';
        $attachmentType = AttachmentType::ANNUAL_REPORT;
        $language = AttachmentLanguage::DUTCH;
        $grounds = ['foo', 'bar'];

        $annualReportDocument = \Mockery::mock(AnnualReportMainDocument::class);
        $annualReportDocument->expects('setFormalDate')->with($formalDate);
        $annualReportDocument->expects('setInternalReference')->with($internalReference);
        $annualReportDocument->expects('setType')->with($attachmentType);
        $annualReportDocument->expects('setLanguage')->with($language);
        $annualReportDocument->expects('setGrounds')->with($grounds);

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

        $this->dossierWorkflowManager->expects('applyTransition')->with($dossier, DossierStatusTransition::UPDATE_MAIN_DOCUMENT);

        $this->annualReportDocumentRepository->expects('findOneByDossierId')->with($dossierUuid)->andReturn($annualReportDocument);

        $validatorList = \Mockery::mock(ConstraintViolationListInterface::class);
        $validatorList->expects('count')->andReturn(3);
        $this->validator->shouldReceive('validate')->andReturn($validatorList);

        $this->expectException(ValidationFailedException::class);

        $this->handler->__invoke(
            new UpdateMainDocumentCommand(
                $dossierUuid,
                $formalDate,
                $internalReference,
                $attachmentType,
                $language,
                $grounds,
                null,
            )
        );
    }
}
