<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Upload\Process;

use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\LoggerInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Upload\Process\DocumentNumberExtractor;
use Shared\Domain\Upload\UploadedFile;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

final class DocumentNumberExtractorTest extends UnitTestCase
{
    #[DataProvider('matchDocumentForFileWithInvalidFilenameProvider')]
    public function testMatchDocumentForFileWithInvalidFilename(string $filename, string $loggedFilename): void
    {
        $wooDecisionId = Uuid::v6();
        $wooDecision = Mockery::mock(WooDecision::class);
        $wooDecision->expects('getId')->andReturn($wooDecisionId);

        $uploadedFile = Mockery::mock(UploadedFile::class);
        $uploadedFile->expects('getOriginalFilename')->andReturn($filename);

        $logger = Mockery::mock(LoggerInterface::class);
        $logger->expects('error')
            ->with('Cannot extract document ID from the filename', [
                'filename' => $loggedFilename,
                'dossierId' => $wooDecisionId,
            ]);

        $documentNumberExtractor = new DocumentNumberExtractor($logger, Mockery::mock(DocumentRepository::class));
        $matchDocumentForFile = $documentNumberExtractor->matchDocumentForFile($uploadedFile, $wooDecision);

        self::assertNull($matchDocumentForFile);
    }

    /**
     * @return array<string,array{filename:string,loggedFilename:string}>
     */
    public static function matchDocumentForFileWithInvalidFilenameProvider(): array
    {
        return [
            'invalid start character is not accepted' => [
                'filename' => '*1234.pdf',
                'loggedFilename' => '*1234',
            ],
            'underscore is not accepted' => [
                'filename' => '_.pdf',
                'loggedFilename' => '_',
            ],
            'extension only' => [
                'filename' => '.pdf',
                'loggedFilename' => '',
            ],
        ];
    }
}
