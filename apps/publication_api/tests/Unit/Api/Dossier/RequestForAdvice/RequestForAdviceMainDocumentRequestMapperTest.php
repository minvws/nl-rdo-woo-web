<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\RequestForAdvice;

use InvalidArgumentException;
use PublicationApi\Api\Dossier\RequestForAdvice\RequestForAdviceMainDocumentRequestDto;
use PublicationApi\Api\Dossier\RequestForAdvice\RequestForAdviceMainDocumentRequestMapper;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdvice;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceMainDocument;
use Shared\Domain\Publication\Ground;
use Shared\Tests\Unit\UnitTestCase;

use function array_map;

final class RequestForAdviceMainDocumentRequestMapperTest extends UnitTestCase
{
    public function testCreate(): void
    {
        $fileName = $this->getFaker()->fileName();
        $formalDate = $this->getFaker()->plainDate();
        $grounds = $this->getFaker()->grounds();
        $language = $this->getFaker()->attachmentLanguage();
        $type = $this->getFaker()->attachmentType(RequestForAdviceMainDocument::getAllowedTypes());

        $requestForAdvice = new RequestForAdvice();

        $requestForAdviceMainDocumentRequestDto = new RequestForAdviceMainDocumentRequestDto(
            $fileName,
            $formalDate,
            $language,
            $type,
            array_map(Ground::from(...), $grounds),
        );

        $mainDocument = RequestForAdviceMainDocumentRequestMapper::create($requestForAdvice, $requestForAdviceMainDocumentRequestDto);

        self::assertSame($requestForAdvice, $mainDocument->getDossier());
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
        $type = $this->getFaker()->attachmentType(RequestForAdviceMainDocument::getAllowedTypes());
        $existingType = $this->getFaker()->attachmentType(RequestForAdviceMainDocument::getAllowedTypes());

        $requestForAdvice = new RequestForAdvice();
        $existingMainDocument = new RequestForAdviceMainDocument(
            $requestForAdvice,
            $this->getFaker()->plainDate(),
            $existingType,
            $existingLanguage,
        );
        $requestForAdvice->setMainDocument($existingMainDocument);

        $requestForAdviceMainDocumentRequestDto = new RequestForAdviceMainDocumentRequestDto(
            $fileName,
            $formalDate,
            $language,
            $type,
            array_map(Ground::from(...), $grounds),
        );

        $mainDocument = RequestForAdviceMainDocumentRequestMapper::update($requestForAdvice, $requestForAdviceMainDocumentRequestDto);

        self::assertSame($existingMainDocument, $mainDocument);
        self::assertSame($fileName->toString(), $mainDocument->getFileInfo()->getName());
        self::assertSame($formalDate, $mainDocument->getFormalDate());
        self::assertSame($grounds, $mainDocument->getGrounds());
        self::assertSame($language, $mainDocument->getLanguage());
        self::assertSame($existingType, $mainDocument->getType());
    }

    public function testUpdateWithoutMainDocument(): void
    {
        $requestForAdvice = new RequestForAdvice();

        $requestForAdviceMainDocumentRequestDto = new RequestForAdviceMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
            $this->getFaker()->attachmentType(RequestForAdviceMainDocument::getAllowedTypes()),
        );

        self::expectException(InvalidArgumentException::class);

        RequestForAdviceMainDocumentRequestMapper::update($requestForAdvice, $requestForAdviceMainDocumentRequestDto);
    }
}
