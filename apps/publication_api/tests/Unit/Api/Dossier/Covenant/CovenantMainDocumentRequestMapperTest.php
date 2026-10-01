<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\Covenant;

use InvalidArgumentException;
use PublicationApi\Api\Dossier\Covenant\CovenantMainDocumentRequestDto;
use PublicationApi\Api\Dossier\Covenant\CovenantMainDocumentRequestMapper;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantMainDocument;
use Shared\Domain\Publication\Ground;
use Shared\Tests\Unit\UnitTestCase;

use function array_map;

final class CovenantMainDocumentRequestMapperTest extends UnitTestCase
{
    public function testCreate(): void
    {
        $fileName = $this->getFaker()->fileName();
        $formalDate = $this->getFaker()->plainDate();
        $grounds = $this->getFaker()->grounds();
        $language = $this->getFaker()->attachmentLanguage();

        $covenant = new Covenant();

        $covenantMainDocumentRequestDto = new CovenantMainDocumentRequestDto(
            $fileName,
            $formalDate,
            $language,
            null,
            array_map(Ground::from(...), $grounds),
        );

        $mainDocument = CovenantMainDocumentRequestMapper::create($covenant, $covenantMainDocumentRequestDto);

        self::assertSame($covenant, $mainDocument->getDossier());
        self::assertSame($fileName->toString(), $mainDocument->getFileInfo()->getName());
        self::assertSame($formalDate, $mainDocument->getFormalDate());
        self::assertSame($grounds, $mainDocument->getGrounds());
        self::assertSame($language, $mainDocument->getLanguage());
        self::assertSame(AttachmentType::COVENANT, $mainDocument->getType());
    }

    public function testUpdate(): void
    {
        $fileName = $this->getFaker()->fileName();
        $formalDate = $this->getFaker()->plainDate();
        $grounds = $this->getFaker()->grounds();
        $language = $this->getFaker()->attachmentLanguage();
        $existingLanguage = $this->getFaker()->attachmentLanguage();

        $covenant = new Covenant();
        $existingMainDocument = new CovenantMainDocument($covenant, $this->getFaker()->plainDate(), $existingLanguage);
        $covenant->setMainDocument($existingMainDocument);

        $covenantMainDocumentRequestDto = new CovenantMainDocumentRequestDto(
            $fileName,
            $formalDate,
            $language,
            null,
            array_map(Ground::from(...), $grounds),
        );

        $mainDocument = CovenantMainDocumentRequestMapper::update($covenant, $covenantMainDocumentRequestDto);

        self::assertSame($existingMainDocument, $mainDocument);
        self::assertSame($fileName->toString(), $mainDocument->getFileInfo()->getName());
        self::assertSame($formalDate, $mainDocument->getFormalDate());
        self::assertSame($grounds, $mainDocument->getGrounds());
        self::assertSame($language, $mainDocument->getLanguage());
    }

    public function testUpdateWithoutMainDocument(): void
    {
        $covenant = new Covenant();

        $covenantMainDocumentRequestDto = new CovenantMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
        );

        self::expectException(InvalidArgumentException::class);

        CovenantMainDocumentRequestMapper::update($covenant, $covenantMainDocumentRequestDto);
    }
}
