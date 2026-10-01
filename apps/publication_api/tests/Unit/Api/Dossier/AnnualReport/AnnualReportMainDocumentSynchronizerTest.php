<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\AnnualReport;

use Mockery;
use PublicationApi\Api\Dossier\AnnualReport\AnnualReportMainDocumentRequestDto;
use PublicationApi\Api\Dossier\AnnualReport\AnnualReportMainDocumentSynchronizer;
use PublicationApi\Api\Dossier\DossierMainDocumentValidator;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReport;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportMainDocument;
use Shared\Domain\Publication\MainDocument\Command\DeleteMainDocumentCommand;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class AnnualReportMainDocumentSynchronizerTest extends UnitTestCase
{
    public function testCreate(): void
    {
        $annualReport = new AnnualReport();

        $dossierMainDocumentValidator = Mockery::mock(DossierMainDocumentValidator::class);
        $dossierMainDocumentValidator->expects('validate')->with(Mockery::type(AnnualReportMainDocument::class));

        $annualReportMainDocumentRequestDto = new AnnualReportMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
            AttachmentType::ANNUAL_REPORT,
        );

        $annualReportMainDocumentSynchronizer = new AnnualReportMainDocumentSynchronizer(
            $dossierMainDocumentValidator,
            Mockery::mock(MessageBusInterface::class),
        );
        $annualReportMainDocumentSynchronizer->create($annualReport, $annualReportMainDocumentRequestDto);

        self::assertInstanceOf(AnnualReportMainDocument::class, $annualReport->getMainDocument());
    }

    public function testUpdateWithExistingMainDocument(): void
    {
        $annualReport = new AnnualReport();
        $existingMainDocument = new AnnualReportMainDocument(
            $annualReport,
            $this->getFaker()->plainDate(),
            AttachmentType::ANNUAL_REPORT,
            $this->getFaker()->attachmentLanguage(),
        );
        $annualReport->setMainDocument($existingMainDocument);

        $dossierMainDocumentValidator = Mockery::mock(DossierMainDocumentValidator::class);
        $dossierMainDocumentValidator->expects('validate')->with($existingMainDocument);

        $annualReportMainDocumentRequestDto = new AnnualReportMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
            AttachmentType::ANNUAL_PLAN,
        );

        $annualReportMainDocumentSynchronizer = new AnnualReportMainDocumentSynchronizer(
            $dossierMainDocumentValidator,
            Mockery::mock(MessageBusInterface::class),
        );
        $annualReportMainDocumentSynchronizer->update($annualReport, $annualReportMainDocumentRequestDto);

        self::assertSame($existingMainDocument, $annualReport->getMainDocument());
    }

    public function testUpdateWithNewMainDocument(): void
    {
        $annualReport = new AnnualReport();

        $dossierMainDocumentValidator = Mockery::mock(DossierMainDocumentValidator::class);
        $dossierMainDocumentValidator->expects('validate')->with(Mockery::type(AnnualReportMainDocument::class));

        $annualReportMainDocumentRequestDto = new AnnualReportMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
            AttachmentType::ANNUAL_REPORT,
        );

        $annualReportMainDocumentSynchronizer = new AnnualReportMainDocumentSynchronizer(
            $dossierMainDocumentValidator,
            Mockery::mock(MessageBusInterface::class),
        );
        $annualReportMainDocumentSynchronizer->update($annualReport, $annualReportMainDocumentRequestDto);

        self::assertInstanceOf(AnnualReportMainDocument::class, $annualReport->getMainDocument());
    }

    public function testDelete(): void
    {
        $annualReport = new AnnualReport();
        $annualReport->setMainDocument(
            new AnnualReportMainDocument(
                $annualReport,
                $this->getFaker()->plainDate(),
                AttachmentType::ANNUAL_REPORT,
                $this->getFaker()->attachmentLanguage(),
            ),
        );

        $messageBus = Mockery::mock(MessageBusInterface::class);
        $messageBus->expects('dispatch')
            ->with(Mockery::on(static function (DeleteMainDocumentCommand $deleteMainDocumentCommand) use ($annualReport): bool {
                return $deleteMainDocumentCommand->dossierId->equals($annualReport->getId());
            }))
            ->andReturn(new Envelope(new DeleteMainDocumentCommand($annualReport->getId())));

        $annualReportMainDocumentSynchronizer = new AnnualReportMainDocumentSynchronizer(
            Mockery::mock(DossierMainDocumentValidator::class),
            $messageBus,
        );

        $annualReportMainDocumentSynchronizer->delete($annualReport);
    }

    public function testDeleteWithoutMainDocument(): void
    {
        $annualReport = new AnnualReport();

        $annualReportMainDocumentSynchronizer = new AnnualReportMainDocumentSynchronizer(
            Mockery::mock(DossierMainDocumentValidator::class),
            Mockery::mock(MessageBusInterface::class),
        );

        $annualReportMainDocumentSynchronizer->delete($annualReport);

        self::assertNull($annualReport->getMainDocument());
    }
}
