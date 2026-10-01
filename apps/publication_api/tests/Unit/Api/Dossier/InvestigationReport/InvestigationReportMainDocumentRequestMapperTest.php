<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\InvestigationReport;

use InvalidArgumentException;
use PublicationApi\Api\Dossier\InvestigationReport\InvestigationReportMainDocumentRequestDto;
use PublicationApi\Api\Dossier\InvestigationReport\InvestigationReportMainDocumentRequestMapper;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportMainDocument;
use Shared\Domain\Publication\Ground;
use Shared\Tests\Unit\UnitTestCase;

use function array_map;

final class InvestigationReportMainDocumentRequestMapperTest extends UnitTestCase
{
    public function testCreate(): void
    {
        $fileName = $this->getFaker()->fileName();
        $formalDate = $this->getFaker()->plainDate();
        $grounds = $this->getFaker()->grounds();
        $language = $this->getFaker()->attachmentLanguage();
        $type = $this->getFaker()->attachmentType(InvestigationReportMainDocument::getAllowedTypes());

        $investigationReport = new InvestigationReport();

        $investigationReportMainDocumentRequestDto = new InvestigationReportMainDocumentRequestDto(
            $fileName,
            $formalDate,
            $language,
            $type,
            array_map(Ground::from(...), $grounds),
        );

        $mainDocument = InvestigationReportMainDocumentRequestMapper::create($investigationReport, $investigationReportMainDocumentRequestDto);

        self::assertSame($investigationReport, $mainDocument->getDossier());
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
        $type = $this->getFaker()->attachmentType(InvestigationReportMainDocument::getAllowedTypes());

        $investigationReport = new InvestigationReport();
        $existingMainDocument = new InvestigationReportMainDocument(
            $investigationReport,
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentType(InvestigationReportMainDocument::getAllowedTypes()),
            $existingLanguage,
        );
        $investigationReport->setMainDocument($existingMainDocument);

        $investigationReportMainDocumentRequestDto = new InvestigationReportMainDocumentRequestDto(
            $fileName,
            $formalDate,
            $language,
            $type,
            array_map(Ground::from(...), $grounds),
        );

        $mainDocument = InvestigationReportMainDocumentRequestMapper::update($investigationReport, $investigationReportMainDocumentRequestDto);

        self::assertSame($existingMainDocument, $mainDocument);
        self::assertSame($fileName->toString(), $mainDocument->getFileInfo()->getName());
        self::assertSame($formalDate, $mainDocument->getFormalDate());
        self::assertSame($grounds, $mainDocument->getGrounds());
        self::assertSame($language, $mainDocument->getLanguage());
        self::assertSame($type, $mainDocument->getType());
    }

    public function testUpdateWithoutMainDocument(): void
    {
        $investigationReport = new InvestigationReport();

        $investigationReportMainDocumentRequestDto = new InvestigationReportMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
            $this->getFaker()->attachmentType(InvestigationReportMainDocument::getAllowedTypes()),
        );

        self::expectException(InvalidArgumentException::class);

        InvestigationReportMainDocumentRequestMapper::update($investigationReport, $investigationReportMainDocumentRequestDto);
    }
}
