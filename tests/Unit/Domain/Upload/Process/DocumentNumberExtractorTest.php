<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Upload\Process;

use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\LoggerInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Upload\Process\DocumentNumberExtractor;
use Shared\Domain\Upload\Process\FileProcessException;
use Shared\Domain\Upload\UploadedFile;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

final class DocumentNumberExtractorTest extends UnitTestCase
{
    private LoggerInterface&MockInterface $logger;
    private WooDecision&MockInterface $dossier;
    private DocumentRepository&MockInterface $documentRepository;
    private DocumentNumberExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dossier = Mockery::mock(WooDecision::class);
        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->documentRepository = Mockery::mock(DocumentRepository::class);

        $this->extractor = new DocumentNumberExtractor(
            $this->logger,
            $this->documentRepository,
        );
    }

    #[DataProvider('getValidFilenameData')]
    public function testExtractWithValidFilename(string $filename, string $expectedDocNr): void
    {
        $this->assertSame(
            $expectedDocNr,
            $this->extractor->extract($filename, $this->dossier),
        );
    }

    #[DataProvider('getInvalidFilenameData')]
    public function testExtractWithInvalidFilename(string $filename): void
    {
        $this->dossier->shouldReceive('getId')->andReturn($expectedDossierId = Uuid::v6());

        $this->logger
            ->shouldReceive('error')
            ->once()
            ->with(
                'Cannot extract document ID from the filename',
                [
                    'filename' => $filename,
                    'matches' => [],
                    'dossierId' => $expectedDossierId,
                ],
            );

        $this->expectExceptionObject(FileProcessException::forFailingToExtractDocumentId($filename, $this->dossier));

        $this->extractor->extract($filename, $this->dossier);
    }

    /**
     * @return array<string,array{filename:string,expectedDocNr:string}>
     */
    public static function getValidFilenameData(): array
    {
        return [
            'numbers-only' => [
                'filename' => '1234.pdf',
                'expectedDocNr' => '1234',
            ],
            'alpha-numerical' => [
                'filename' => '1234abc.pdf',
                'expectedDocNr' => '1234abc',
            ],
            'alpha-numerical-mixed' => [
                'filename' => '1234abc789xyz.pdf',
                'expectedDocNr' => '1234abc789xyz',
            ],
            'alpha-numerical-with-dashes' => [
                'filename' => '1234abc7-89xyz.pdf',
                'expectedDocNr' => '1234abc7-89xyz',
            ],
            'dashes-only' => [
                'filename' => '---.pdf',
                'expectedDocNr' => '---',
            ],
            'characters-after-whitespace-are-ignored' => [
                'filename' => '1234 - test.pdf',
                'expectedDocNr' => '1234',
            ],
        ];
    }

    /**
     * @return array<string,array{filename:string}>
     */
    public static function getInvalidFilenameData(): array
    {
        return [
            'invalid-start-character-is-not-accepted' => [
                'filename' => '*1234.pdf',
            ],
            'underscore-is-not-accepted' => [
                'filename' => '_.pdf',
            ],
        ];
    }

    public function testMatchDocumentForFile(): void
    {
        $document = Mockery::mock(Document::class);

        $file = Mockery::mock(UploadedFile::class);
        $file->shouldReceive('getOriginalFilename')->andReturn('1234.pdf');

        $this->documentRepository
            ->expects('findOneByDossierAndDocumentId')
            ->with($this->dossier, '1234')
            ->andReturn($document);

        self::assertSame(
            $document,
            $this->extractor->matchDocumentForFile($file, $this->dossier),
        );
    }

    public function testMatchDocumentForFileReturnsNullForFileProcessException(): void
    {
        $this->dossier->shouldReceive('getId')->andReturn(Uuid::v6());

        $file = Mockery::mock(UploadedFile::class);
        $file->shouldReceive('getOriginalFilename')->andReturn('.pdf');

        $this->logger->expects('error');

        self::assertNull(
            $this->extractor->matchDocumentForFile($file, $this->dossier),
        );
    }
}
