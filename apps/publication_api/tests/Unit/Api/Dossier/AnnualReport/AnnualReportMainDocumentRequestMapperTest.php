<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\AnnualReport;

use InvalidArgumentException;
use PublicationApi\Api\Dossier\AnnualReport\AnnualReportMainDocumentRequestDto;
use PublicationApi\Api\Dossier\AnnualReport\AnnualReportMainDocumentRequestMapper;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReport;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportMainDocument;
use Shared\Domain\Publication\Ground;
use Shared\Tests\Unit\UnitTestCase;

use function array_map;

final class AnnualReportMainDocumentRequestMapperTest extends UnitTestCase
{
    public function testCreate(): void
    {
        $fileName = $this->getFaker()->fileName();
        $formalDate = $this->getFaker()->plainDate();
        $grounds = $this->getFaker()->grounds();
        $language = $this->getFaker()->attachmentLanguage();
        $type = AttachmentType::ANNUAL_REPORT;

        $annualReport = new AnnualReport();

        $annualReportMainDocumentRequestDto = new AnnualReportMainDocumentRequestDto(
            $fileName,
            $formalDate,
            $language,
            $type,
            array_map(Ground::from(...), $grounds),
        );

        $mainDocument = AnnualReportMainDocumentRequestMapper::create($annualReport, $annualReportMainDocumentRequestDto);

        self::assertSame($annualReport, $mainDocument->getDossier());
        self::assertSame($fileName->toString(), $mainDocument->getFileInfo()->getName());
        self::assertSame($formalDate, $mainDocument->getFormalDate());
        self::assertSame($grounds, $mainDocument->getGrounds());
        self::assertSame($language, $mainDocument->getLanguage());
        self::assertSame($type, $mainDocument->getType());
    }

    public function testUpdate(): void
    {
        $fileName = $this->getFaker()->fileName();
        $formalDate = $this->getFaker()->plainDate();
        $grounds = $this->getFaker()->grounds();
        $language = $this->getFaker()->attachmentLanguage();
        $existingLanguage = $this->getFaker()->attachmentLanguage();
        $type = AttachmentType::ANNUAL_PLAN;

        $annualReport = new AnnualReport();
        $existingMainDocument = new AnnualReportMainDocument(
            $annualReport,
            $this->getFaker()->plainDate(),
            AttachmentType::ANNUAL_REPORT,
            $existingLanguage,
        );
        $annualReport->setMainDocument($existingMainDocument);

        $annualReportMainDocumentRequestDto = new AnnualReportMainDocumentRequestDto(
            $fileName,
            $formalDate,
            $language,
            $type,
            array_map(Ground::from(...), $grounds),
        );

        $mainDocument = AnnualReportMainDocumentRequestMapper::update($annualReport, $annualReportMainDocumentRequestDto);

        self::assertSame($existingMainDocument, $mainDocument);
        self::assertSame($fileName->toString(), $mainDocument->getFileInfo()->getName());
        self::assertSame($formalDate, $mainDocument->getFormalDate());
        self::assertSame($grounds, $mainDocument->getGrounds());
        self::assertSame($language, $mainDocument->getLanguage());
        self::assertSame($type, $mainDocument->getType());
    }

    public function testUpdateWithoutMainDocument(): void
    {
        $annualReport = new AnnualReport();

        $annualReportMainDocumentRequestDto = new AnnualReportMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
            AttachmentType::ANNUAL_REPORT,
        );

        self::expectException(InvalidArgumentException::class);

        AnnualReportMainDocumentRequestMapper::update($annualReport, $annualReportMainDocumentRequestDto);
    }
}
