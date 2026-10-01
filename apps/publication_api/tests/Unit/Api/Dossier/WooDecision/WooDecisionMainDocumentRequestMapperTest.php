<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\WooDecision;

use InvalidArgumentException;
use PublicationApi\Api\Dossier\WooDecision\WooDecisionMainDocumentRequestDto;
use PublicationApi\Api\Dossier\WooDecision\WooDecisionMainDocumentRequestMapper;
use Shared\Domain\Publication\Dossier\Type\WooDecision\MainDocument\WooDecisionMainDocument;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Ground;
use Shared\Tests\Unit\UnitTestCase;

use function array_map;

final class WooDecisionMainDocumentRequestMapperTest extends UnitTestCase
{
    public function testCreate(): void
    {
        $fileName = $this->getFaker()->fileName();
        $formalDate = $this->getFaker()->plainDate();
        $grounds = $this->getFaker()->grounds();
        $language = $this->getFaker()->attachmentLanguage();

        $wooDecision = new WooDecision();

        $wooDecisionMainDocumentRequestDto = new WooDecisionMainDocumentRequestDto(
            $fileName,
            $formalDate,
            $language,
            grounds: array_map(Ground::from(...), $grounds),
        );

        $mainDocument = WooDecisionMainDocumentRequestMapper::create($wooDecision, $wooDecisionMainDocumentRequestDto);

        self::assertSame($wooDecision, $mainDocument->getDossier());
        self::assertSame($fileName->toString(), $mainDocument->getFileInfo()->getName());
        self::assertSame($formalDate, $mainDocument->getFormalDate());
        self::assertSame($grounds, $mainDocument->getGrounds());
        self::assertSame($language, $mainDocument->getLanguage());
    }

    public function testUpdate(): void
    {
        $fileName = $this->getFaker()->fileName();
        $formalDate = $this->getFaker()->plainDate();
        $grounds = $this->getFaker()->grounds();
        $language = $this->getFaker()->attachmentLanguage();
        $existingLanguage = $this->getFaker()->attachmentLanguage();

        $wooDecision = new WooDecision();
        $existingMainDocument = new WooDecisionMainDocument($wooDecision, $this->getFaker()->plainDate(), $existingLanguage);
        $wooDecision->setMainDocument($existingMainDocument);

        $wooDecisionMainDocumentRequestDto = new WooDecisionMainDocumentRequestDto(
            $fileName,
            $formalDate,
            $language,
            grounds: array_map(Ground::from(...), $grounds),
        );

        $mainDocument = WooDecisionMainDocumentRequestMapper::update($wooDecision, $wooDecisionMainDocumentRequestDto);

        self::assertSame($existingMainDocument, $mainDocument);
        self::assertSame($fileName->toString(), $mainDocument->getFileInfo()->getName());
        self::assertSame($formalDate, $mainDocument->getFormalDate());
        self::assertSame($grounds, $mainDocument->getGrounds());
        self::assertSame($language, $mainDocument->getLanguage());
    }

    public function testUpdateFailsWhenNoMainDocument(): void
    {
        $wooDecision = new WooDecision();

        $wooDecisionMainDocumentRequestDto = new WooDecisionMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
        );

        self::expectException(InvalidArgumentException::class);

        WooDecisionMainDocumentRequestMapper::update($wooDecision, $wooDecisionMainDocumentRequestDto);
    }
}
