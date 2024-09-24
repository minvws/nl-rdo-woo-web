<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Upload\Process;

use App\Domain\Upload\Process\DocumentNumberExtractor;
use App\Domain\Upload\Process\FileProcessException;
use App\Entity\Dossier;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

final class DocumentNumberExtractorTest extends UnitTestCase
{
    private LoggerInterface&MockInterface $logger;
    private Dossier&MockInterface $dossier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->dossier = \Mockery::mock(Dossier::class);
    }

    #[DataProvider('getValidFilenameData')]
    public function testExtractWithValidFilename(string $filename, string $expectedDocNr): void
    {
        $extractor = new DocumentNumberExtractor($this->logger);

        $this->assertSame($expectedDocNr, $extractor->extract($filename, $this->dossier));
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

        $extractor = new DocumentNumberExtractor($this->logger);

        $this->expectExceptionObject(FileProcessException::forFailingToExtractDocumentId($filename, $this->dossier));

        $extractor->extract($filename, $this->dossier);
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
}
