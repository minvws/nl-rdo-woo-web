<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\ComplaintJudgement;

use InvalidArgumentException;
use PublicationApi\Api\Dossier\ComplaintJudgement\ComplaintJudgementMainDocumentRequestDto;
use PublicationApi\Api\Dossier\ComplaintJudgement\ComplaintJudgementMainDocumentRequestMapper;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgement;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementMainDocument;
use Shared\Domain\Publication\Ground;
use Shared\Tests\Unit\UnitTestCase;

use function array_map;

final class ComplaintJudgementMainDocumentRequestMapperTest extends UnitTestCase
{
    public function testCreate(): void
    {
        $fileName = $this->getFaker()->fileName();
        $formalDate = $this->getFaker()->plainDate();
        $grounds = $this->getFaker()->grounds();
        $language = $this->getFaker()->attachmentLanguage();
        $type = $this->getFaker()->attachmentType(ComplaintJudgementMainDocument::getAllowedTypes());

        $complaintJudgement = new ComplaintJudgement();

        $complaintJudgementMainDocumentRequestDto = new ComplaintJudgementMainDocumentRequestDto(
            $fileName,
            $formalDate,
            $language,
            $type,
            array_map(Ground::from(...), $grounds),
        );

        $mainDocument = ComplaintJudgementMainDocumentRequestMapper::create($complaintJudgement, $complaintJudgementMainDocumentRequestDto);

        self::assertSame($complaintJudgement, $mainDocument->getDossier());
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
        $type = $this->getFaker()->attachmentType(ComplaintJudgementMainDocument::getAllowedTypes());
        $existingType = $this->getFaker()->attachmentType(ComplaintJudgementMainDocument::getAllowedTypes());

        $complaintJudgement = new ComplaintJudgement();
        $existingMainDocument = new ComplaintJudgementMainDocument(
            $complaintJudgement,
            $this->getFaker()->plainDate(),
            $existingType,
            $existingLanguage,
        );
        $complaintJudgement->setMainDocument($existingMainDocument);

        $complaintJudgementMainDocumentRequestDto = new ComplaintJudgementMainDocumentRequestDto(
            $fileName,
            $formalDate,
            $language,
            $type,
            array_map(Ground::from(...), $grounds),
        );

        $mainDocument = ComplaintJudgementMainDocumentRequestMapper::update($complaintJudgement, $complaintJudgementMainDocumentRequestDto);

        self::assertSame($existingMainDocument, $mainDocument);
        self::assertSame($fileName->toString(), $mainDocument->getFileInfo()->getName());
        self::assertSame($formalDate, $mainDocument->getFormalDate());
        self::assertSame($grounds, $mainDocument->getGrounds());
        self::assertSame($language, $mainDocument->getLanguage());
        self::assertSame($existingType, $mainDocument->getType());
    }

    public function testUpdateWithoutMainDocument(): void
    {
        $complaintJudgement = new ComplaintJudgement();

        $complaintJudgementMainDocumentRequestDto = new ComplaintJudgementMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
            $this->getFaker()->attachmentType(ComplaintJudgementMainDocument::getAllowedTypes()),
        );

        self::expectException(InvalidArgumentException::class);

        ComplaintJudgementMainDocumentRequestMapper::update($complaintJudgement, $complaintJudgementMainDocumentRequestDto);
    }
}
