<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\DraftDecision;

use InvalidArgumentException;
use PublicationApi\Api\Dossier\DraftDecision\DraftDecisionMainDocumentRequestDto;
use PublicationApi\Api\Dossier\DraftDecision\DraftDecisionMainDocumentRequestMapper;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecision;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecisionMainDocument;
use Shared\Tests\Unit\UnitTestCase;

final class DraftDecisionMainDocumentRequestMapperTest extends UnitTestCase
{
    public function testCreate(): void
    {
        $fileName = $this->getFaker()->fileName();
        $formalDate = $this->getFaker()->plainDate();
        $language = $this->getFaker()->attachmentLanguage();
        $type = $this->getFaker()->attachmentType(DraftDecisionMainDocument::getAllowedTypes());

        $draftDecision = new DraftDecision();

        $draftDecisionMainDocumentRequestDto = new DraftDecisionMainDocumentRequestDto($fileName, $formalDate, $language, $type);

        $mainDocument = DraftDecisionMainDocumentRequestMapper::create($draftDecision, $draftDecisionMainDocumentRequestDto);

        self::assertSame($draftDecision, $mainDocument->getDossier());
        self::assertSame($fileName->toString(), $mainDocument->getFileInfo()->getName());
        self::assertSame($formalDate, $mainDocument->getFormalDate());
        self::assertSame($language, $mainDocument->getLanguage());
        self::assertSame($type, $mainDocument->getType());
        self::assertSame([], $mainDocument->getGrounds());
    }

    public function testUpdate(): void
    {
        $fileName = $this->getFaker()->fileName();
        $formalDate = $this->getFaker()->plainDate();
        $language = $this->getFaker()->attachmentLanguage();
        $existingLanguage = $this->getFaker()->attachmentLanguage();
        $type = $this->getFaker()->attachmentType(DraftDecisionMainDocument::getAllowedTypes());
        $existingType = $this->getFaker()->attachmentType(DraftDecisionMainDocument::getAllowedTypes());

        $draftDecision = new DraftDecision();
        $existingMainDocument = new DraftDecisionMainDocument(
            $draftDecision,
            $this->getFaker()->plainDate(),
            $existingType,
            $existingLanguage,
        );
        $draftDecision->setMainDocument($existingMainDocument);

        $draftDecisionMainDocumentRequestDto = new DraftDecisionMainDocumentRequestDto($fileName, $formalDate, $language, $type);

        $mainDocument = DraftDecisionMainDocumentRequestMapper::update($draftDecision, $draftDecisionMainDocumentRequestDto);

        self::assertSame($existingMainDocument, $mainDocument);
        self::assertSame($fileName->toString(), $mainDocument->getFileInfo()->getName());
        self::assertSame($formalDate, $mainDocument->getFormalDate());
        self::assertSame($language, $mainDocument->getLanguage());
        self::assertSame($existingType, $mainDocument->getType());
    }

    public function testUpdateWithoutMainDocument(): void
    {
        $draftDecision = new DraftDecision();

        $draftDecisionMainDocumentRequestDto = new DraftDecisionMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
            $this->getFaker()->attachmentType(DraftDecisionMainDocument::getAllowedTypes()),
        );

        self::expectException(InvalidArgumentException::class);

        DraftDecisionMainDocumentRequestMapper::update($draftDecision, $draftDecisionMainDocumentRequestDto);
    }
}
