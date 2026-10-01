<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\Advice;

use InvalidArgumentException;
use PublicationApi\Api\Dossier\Advice\AdviceMainDocumentRequestDto;
use PublicationApi\Api\Dossier\Advice\AdviceMainDocumentRequestMapper;
use Shared\Domain\Publication\Dossier\Type\Advice\Advice;
use Shared\Domain\Publication\Dossier\Type\Advice\AdviceMainDocument;
use Shared\Domain\Publication\Ground;
use Shared\Tests\Unit\UnitTestCase;

use function array_map;

final class AdviceMainDocumentRequestMapperTest extends UnitTestCase
{
    public function testCreate(): void
    {
        $fileName = $this->getFaker()->fileName();
        $formalDate = $this->getFaker()->plainDate();
        $grounds = $this->getFaker()->grounds();
        $language = $this->getFaker()->attachmentLanguage();
        $type = $this->getFaker()->attachmentType(AdviceMainDocument::getAllowedTypes());

        $advice = new Advice();

        $adviceMainDocumentRequestDto = new AdviceMainDocumentRequestDto(
            $fileName,
            $formalDate,
            $language,
            $type,
            array_map(Ground::from(...), $grounds),
        );

        $mainDocument = AdviceMainDocumentRequestMapper::create($advice, $adviceMainDocumentRequestDto);

        self::assertSame($advice, $mainDocument->getDossier());
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
        $type = $this->getFaker()->attachmentType(AdviceMainDocument::getAllowedTypes());
        $existingType = $this->getFaker()->attachmentType(AdviceMainDocument::getAllowedTypes());

        $advice = new Advice();
        $existingMainDocument = new AdviceMainDocument(
            $advice,
            $this->getFaker()->plainDate(),
            $existingType,
            $existingLanguage,
        );
        $advice->setMainDocument($existingMainDocument);

        $adviceMainDocumentRequestDto = new AdviceMainDocumentRequestDto(
            $fileName,
            $formalDate,
            $language,
            $type,
            array_map(Ground::from(...), $grounds),
        );

        $mainDocument = AdviceMainDocumentRequestMapper::update($advice, $adviceMainDocumentRequestDto);

        self::assertSame($existingMainDocument, $mainDocument);
        self::assertSame($fileName->toString(), $mainDocument->getFileInfo()->getName());
        self::assertSame($formalDate, $mainDocument->getFormalDate());
        self::assertSame($grounds, $mainDocument->getGrounds());
        self::assertSame($language, $mainDocument->getLanguage());
        self::assertSame($existingType, $mainDocument->getType());
    }

    public function testUpdateWithoutMainDocument(): void
    {
        $advice = new Advice();

        $adviceMainDocumentRequestDto = new AdviceMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
            $this->getFaker()->attachmentType(AdviceMainDocument::getAllowedTypes()),
        );

        self::expectException(InvalidArgumentException::class);

        AdviceMainDocumentRequestMapper::update($advice, $adviceMainDocumentRequestDto);
    }
}
