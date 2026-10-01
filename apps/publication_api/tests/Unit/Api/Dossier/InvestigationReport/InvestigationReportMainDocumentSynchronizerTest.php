<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\InvestigationReport;

use Mockery;
use PublicationApi\Api\Dossier\DossierMainDocumentValidator;
use PublicationApi\Api\Dossier\InvestigationReport\InvestigationReportMainDocumentRequestDto;
use PublicationApi\Api\Dossier\InvestigationReport\InvestigationReportMainDocumentSynchronizer;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportMainDocument;
use Shared\Domain\Publication\MainDocument\Command\DeleteMainDocumentCommand;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class InvestigationReportMainDocumentSynchronizerTest extends UnitTestCase
{
    public function testCreate(): void
    {
        $investigationReport = new InvestigationReport();

        $dossierMainDocumentValidator = Mockery::mock(DossierMainDocumentValidator::class);
        $dossierMainDocumentValidator->expects('validate')->with(Mockery::type(InvestigationReportMainDocument::class));

        $investigationReportMainDocumentRequestDto = new InvestigationReportMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
            $this->getFaker()->attachmentType(InvestigationReportMainDocument::getAllowedTypes()),
        );

        $investigationReportMainDocumentSynchronizer = new InvestigationReportMainDocumentSynchronizer(
            $dossierMainDocumentValidator,
            Mockery::mock(MessageBusInterface::class),
        );
        $investigationReportMainDocumentSynchronizer->create($investigationReport, $investigationReportMainDocumentRequestDto);

        self::assertInstanceOf(InvestigationReportMainDocument::class, $investigationReport->getMainDocument());
    }

    public function testUpdateWithExistingMainDocument(): void
    {
        $investigationReport = new InvestigationReport();
        $existingMainDocument = new InvestigationReportMainDocument(
            $investigationReport,
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentType(InvestigationReportMainDocument::getAllowedTypes()),
            $this->getFaker()->attachmentLanguage(),
        );
        $investigationReport->setMainDocument($existingMainDocument);

        $dossierMainDocumentValidator = Mockery::mock(DossierMainDocumentValidator::class);
        $dossierMainDocumentValidator->expects('validate')->with($existingMainDocument);

        $investigationReportMainDocumentRequestDto = new InvestigationReportMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
            $this->getFaker()->attachmentType(InvestigationReportMainDocument::getAllowedTypes()),
        );

        $investigationReportMainDocumentSynchronizer = new InvestigationReportMainDocumentSynchronizer(
            $dossierMainDocumentValidator,
            Mockery::mock(MessageBusInterface::class),
        );
        $investigationReportMainDocumentSynchronizer->update($investigationReport, $investigationReportMainDocumentRequestDto);

        self::assertSame($existingMainDocument, $investigationReport->getMainDocument());
    }

    public function testUpdateWithNewMainDocument(): void
    {
        $investigationReport = new InvestigationReport();

        $dossierMainDocumentValidator = Mockery::mock(DossierMainDocumentValidator::class);
        $dossierMainDocumentValidator->expects('validate')->with(Mockery::type(InvestigationReportMainDocument::class));

        $investigationReportMainDocumentRequestDto = new InvestigationReportMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
            $this->getFaker()->attachmentType(InvestigationReportMainDocument::getAllowedTypes()),
        );

        $investigationReportMainDocumentSynchronizer = new InvestigationReportMainDocumentSynchronizer(
            $dossierMainDocumentValidator,
            Mockery::mock(MessageBusInterface::class),
        );
        $investigationReportMainDocumentSynchronizer->update($investigationReport, $investigationReportMainDocumentRequestDto);

        self::assertInstanceOf(InvestigationReportMainDocument::class, $investigationReport->getMainDocument());
    }

    public function testDelete(): void
    {
        $investigationReport = new InvestigationReport();
        $investigationReport->setMainDocument(
            new InvestigationReportMainDocument(
                $investigationReport,
                $this->getFaker()->plainDate(),
                $this->getFaker()->attachmentType(InvestigationReportMainDocument::getAllowedTypes()),
                $this->getFaker()->attachmentLanguage(),
            ),
        );

        $messageBus = Mockery::mock(MessageBusInterface::class);
        $messageBus->expects('dispatch')
            ->with(Mockery::on(static function (DeleteMainDocumentCommand $deleteMainDocumentCommand) use ($investigationReport): bool {
                return $deleteMainDocumentCommand->dossierId->equals($investigationReport->getId());
            }))
            ->andReturn(new Envelope(new DeleteMainDocumentCommand($investigationReport->getId())));

        $investigationReportMainDocumentSynchronizer = new InvestigationReportMainDocumentSynchronizer(
            Mockery::mock(DossierMainDocumentValidator::class),
            $messageBus,
        );

        $investigationReportMainDocumentSynchronizer->delete($investigationReport);
    }

    public function testDeleteWithoutMainDocument(): void
    {
        $investigationReport = new InvestigationReport();

        $investigationReportMainDocumentSynchronizer = new InvestigationReportMainDocumentSynchronizer(
            Mockery::mock(DossierMainDocumentValidator::class),
            Mockery::mock(MessageBusInterface::class),
        );

        $investigationReportMainDocumentSynchronizer->delete($investigationReport);

        self::assertNull($investigationReport->getMainDocument());
    }
}
