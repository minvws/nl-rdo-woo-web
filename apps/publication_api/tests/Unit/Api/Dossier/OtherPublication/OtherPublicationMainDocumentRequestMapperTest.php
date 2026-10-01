<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\OtherPublication;

use InvalidArgumentException;
use PublicationApi\Api\Dossier\OtherPublication\OtherPublicationMainDocumentRequestDto;
use PublicationApi\Api\Dossier\OtherPublication\OtherPublicationMainDocumentRequestMapper;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublication;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublicationMainDocument;
use Shared\Domain\Publication\Ground;
use Shared\Tests\Unit\UnitTestCase;

use function array_map;

final class OtherPublicationMainDocumentRequestMapperTest extends UnitTestCase
{
    public function testCreate(): void
    {
        $fileName = $this->getFaker()->fileName();
        $formalDate = $this->getFaker()->plainDate();
        $grounds = $this->getFaker()->grounds();
        $language = $this->getFaker()->attachmentLanguage();
        $type = $this->getFaker()->attachmentType(OtherPublicationMainDocument::getAllowedTypes());

        $otherPublication = new OtherPublication();

        $otherPublicationMainDocumentRequestDto = new OtherPublicationMainDocumentRequestDto(
            $fileName,
            $formalDate,
            $language,
            $type,
            array_map(Ground::from(...), $grounds),
        );

        $mainDocument = OtherPublicationMainDocumentRequestMapper::create($otherPublication, $otherPublicationMainDocumentRequestDto);

        self::assertSame($otherPublication, $mainDocument->getDossier());
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
        $type = $this->getFaker()->attachmentType(OtherPublicationMainDocument::getAllowedTypes());

        $otherPublication = new OtherPublication();
        $existingMainDocument = new OtherPublicationMainDocument(
            $otherPublication,
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentType(OtherPublicationMainDocument::getAllowedTypes()),
            $existingLanguage,
        );
        $otherPublication->setMainDocument($existingMainDocument);

        $otherPublicationMainDocumentRequestDto = new OtherPublicationMainDocumentRequestDto(
            $fileName,
            $formalDate,
            $language,
            $type,
            array_map(Ground::from(...), $grounds),
        );

        $mainDocument = OtherPublicationMainDocumentRequestMapper::update($otherPublication, $otherPublicationMainDocumentRequestDto);

        self::assertSame($existingMainDocument, $mainDocument);
        self::assertSame($fileName->toString(), $mainDocument->getFileInfo()->getName());
        self::assertSame($formalDate, $mainDocument->getFormalDate());
        self::assertSame($grounds, $mainDocument->getGrounds());
        self::assertSame($language, $mainDocument->getLanguage());
        self::assertSame($type, $mainDocument->getType());
    }

    public function testUpdateWithoutMainDocument(): void
    {
        $otherPublication = new OtherPublication();

        $otherPublicationMainDocumentRequestDto = new OtherPublicationMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
            $this->getFaker()->attachmentType(OtherPublicationMainDocument::getAllowedTypes()),
        );

        self::expectException(InvalidArgumentException::class);

        OtherPublicationMainDocumentRequestMapper::update($otherPublication, $otherPublicationMainDocumentRequestDto);
    }
}
