<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\Disposition;

use InvalidArgumentException;
use PublicationApi\Api\Dossier\Disposition\DispositionMainDocumentRequestDto;
use PublicationApi\Api\Dossier\Disposition\DispositionMainDocumentRequestMapper;
use Shared\Domain\Publication\Dossier\Type\Disposition\Disposition;
use Shared\Domain\Publication\Dossier\Type\Disposition\DispositionMainDocument;
use Shared\Domain\Publication\Ground;
use Shared\Tests\Unit\UnitTestCase;

use function array_map;

final class DispositionMainDocumentRequestMapperTest extends UnitTestCase
{
    public function testCreate(): void
    {
        $fileName = $this->getFaker()->fileName();
        $formalDate = $this->getFaker()->plainDate();
        $grounds = $this->getFaker()->grounds();
        $language = $this->getFaker()->attachmentLanguage();
        $type = $this->getFaker()->attachmentType(DispositionMainDocument::getAllowedTypes());

        $disposition = new Disposition();

        $dispositionMainDocumentRequestDto = new DispositionMainDocumentRequestDto(
            $fileName,
            $formalDate,
            $language,
            $type,
            array_map(Ground::from(...), $grounds),
        );

        $mainDocument = DispositionMainDocumentRequestMapper::create($disposition, $dispositionMainDocumentRequestDto);

        self::assertSame($disposition, $mainDocument->getDossier());
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
        $type = $this->getFaker()->attachmentType(DispositionMainDocument::getAllowedTypes());

        $disposition = new Disposition();
        $existingMainDocument = new DispositionMainDocument(
            $disposition,
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentType(DispositionMainDocument::getAllowedTypes()),
            $existingLanguage,
        );
        $disposition->setMainDocument($existingMainDocument);

        $dispositionMainDocumentRequestDto = new DispositionMainDocumentRequestDto(
            $fileName,
            $formalDate,
            $language,
            $type,
            array_map(Ground::from(...), $grounds),
        );

        $mainDocument = DispositionMainDocumentRequestMapper::update($disposition, $dispositionMainDocumentRequestDto);

        self::assertSame($existingMainDocument, $mainDocument);
        self::assertSame($fileName->toString(), $mainDocument->getFileInfo()->getName());
        self::assertSame($formalDate, $mainDocument->getFormalDate());
        self::assertSame($grounds, $mainDocument->getGrounds());
        self::assertSame($language, $mainDocument->getLanguage());
        self::assertSame($type, $mainDocument->getType());
    }

    public function testUpdateWithoutMainDocument(): void
    {
        $disposition = new Disposition();

        $dispositionMainDocumentRequestDto = new DispositionMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
            $this->getFaker()->attachmentType(DispositionMainDocument::getAllowedTypes()),
        );

        self::expectException(InvalidArgumentException::class);

        DispositionMainDocumentRequestMapper::update($disposition, $dispositionMainDocumentRequestDto);
    }
}
