<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Domain\Upload\Process;

use PHPUnit\Framework\Attributes\DataProvider;
use Shared\Domain\Upload\Process\DocumentNumberExtractor;
use Shared\Domain\Upload\UploadedFile;
use Shared\Tests\Factory\DocumentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Shared\Tests\Integration\SharedWebTestCase;

final class DocumentNumberExtractorTest extends SharedWebTestCase
{
    #[DataProvider('matchDocumentForFileProvider')]
    public function testMatchDocumentForFile(string $documentId, string $filename): void
    {
        $wooDecision = WooDecisionFactory::createOne();
        $document = DocumentFactory::createOne([
            'dossiers' => [$wooDecision],
            'documentId' => $documentId,
        ]);

        $documentNumberExtractor = self::fromContainer(DocumentNumberExtractor::class);
        $matchDocumentForFile = $documentNumberExtractor->matchDocumentForFile(new UploadedFile($filename), $wooDecision);

        self::assertNotNull($matchDocumentForFile);
        self::assertSame($document->getId(), $matchDocumentForFile->getId());
    }

    /**
     * @return array<string,array{documentId:string,filename:string}>
     */
    public static function matchDocumentForFileProvider(): array
    {
        return [
            'numbers only' => [
                'documentId' => '1234',
                'filename' => '1234.pdf',
            ],
            'alpha numerical' => [
                'documentId' => '1234abc',
                'filename' => '1234abc.pdf',
            ],
            'document number with dashes' => [
                'documentId' => '03eb7aee-828c-4ecf-a7d6-ddb81ca25a51',
                'filename' => '03eb7aee-828c-4ecf-a7d6-ddb81ca25a51.pdf',
            ],
            'document number with dots' => [
                'documentId' => 'abc.1',
                'filename' => 'abc.1.pdf',
            ],
            'document number with dashes and dots' => [
                'documentId' => '1234-abc.1',
                'filename' => '1234-abc.1.pdf',
            ],
            'description after dash' => [
                'documentId' => '1234',
                'filename' => '1234-omschrijving.pdf',
            ],
            'description after dot' => [
                'documentId' => '1234',
                'filename' => '1234.omschrijving.pdf',
            ],
            'description after whitespace' => [
                'documentId' => '1234',
                'filename' => '1234 - omschrijving.pdf',
            ],
            'description after underscore' => [
                'documentId' => '1234',
                'filename' => '1234_omschrijving.pdf',
            ],
        ];
    }

    public function testMatchDocumentForFilePrefersFullDocumentNumberOverPrefix(): void
    {
        $wooDecision = WooDecisionFactory::createOne();
        DocumentFactory::createOne([
            'dossiers' => [$wooDecision],
            'documentId' => 'abc',
        ]);
        $document = DocumentFactory::createOne([
            'dossiers' => [$wooDecision],
            'documentId' => 'abc-1',
        ]);

        $documentNumberExtractor = self::fromContainer(DocumentNumberExtractor::class);
        $matchDocumentForFile = $documentNumberExtractor->matchDocumentForFile(new UploadedFile('abc-1.pdf'), $wooDecision);

        self::assertNotNull($matchDocumentForFile);
        self::assertSame($document->getId(), $matchDocumentForFile->getId());
    }

    #[DataProvider('matchDocumentForFileWithoutMatchingDocumentProvider')]
    public function testMatchDocumentForFileWithoutMatchingDocument(string $filename): void
    {
        $wooDecision = WooDecisionFactory::createOne();
        DocumentFactory::createOne([
            'dossiers' => [$wooDecision],
            'documentId' => '1234',
        ]);

        $documentNumberExtractor = self::fromContainer(DocumentNumberExtractor::class);
        $matchDocumentForFile = $documentNumberExtractor->matchDocumentForFile(new UploadedFile($filename), $wooDecision);

        self::assertNull($matchDocumentForFile);
    }

    /**
     * @return array<string,array{filename:string}>
     */
    public static function matchDocumentForFileWithoutMatchingDocumentProvider(): array
    {
        return [
            'other document number' => [
                'filename' => '5678.pdf',
            ],
            'dashes only' => [
                'filename' => '---.pdf',
            ],
            'invalid start character' => [
                'filename' => '*1234.pdf',
            ],
            'extension only' => [
                'filename' => '.pdf',
            ],
        ];
    }

    public function testMatchDocumentForFileIgnoresDocumentOfOtherWooDecision(): void
    {
        $wooDecision = WooDecisionFactory::createOne();
        DocumentFactory::createOne([
            'dossiers' => [WooDecisionFactory::createOne()],
            'documentId' => '1234',
        ]);

        $documentNumberExtractor = self::fromContainer(DocumentNumberExtractor::class);
        $matchDocumentForFile = $documentNumberExtractor->matchDocumentForFile(new UploadedFile('1234.pdf'), $wooDecision);

        self::assertNull($matchDocumentForFile);
    }
}
