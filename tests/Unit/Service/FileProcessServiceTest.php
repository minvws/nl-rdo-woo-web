<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Domain\Ingest\SubType\SubTypeIngester;
use App\Entity\Dossier;
use App\Service\FileProcessService;
use App\Service\HistoryService;
use App\Service\Storage\EntityStorageService;
use Doctrine\ORM\EntityManagerInterface;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

class FileProcessServiceTest extends MockeryTestCase
{
    private EntityManagerInterface&MockInterface $entityManager;
    private MockInterface&EntityStorageService $entityStorageService;
    private LoggerInterface&MockInterface $logger;
    private SubTypeIngester&MockInterface $ingestService;
    private FileProcessService $service;
    private HistoryService&MockInterface $historyService;

    public function setUp(): void
    {
        $this->entityManager = \Mockery::mock(EntityManagerInterface::class);
        $this->entityStorageService = \Mockery::mock(EntityStorageService::class);
        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->logger->shouldReceive('error');
        $this->ingestService = \Mockery::mock(SubTypeIngester::class);
        $this->historyService = \Mockery::mock(HistoryService::class);

        $this->service = new FileProcessService(
            $this->entityManager,
            $this->entityStorageService,
            $this->logger,
            $this->ingestService,
            $this->historyService,
        );

        parent::setUp();
    }

    #[DataProvider('getDocumentNumberFromFileNameProvider')]
    public function testGetDocumentNumberFromFileName(string $filename, string $expectedDocNr, bool $expectException = false): void
    {
        $uuid = Uuid::v6();
        $dossier = \Mockery::mock(Dossier::class);
        $dossier->shouldReceive('getId')->andReturn($uuid);

        if ($expectException) {
            $this->expectException(\RuntimeException::class);
        }

        self::assertEquals(
            $expectedDocNr,
            $this->service->getDocumentNumberFromFilename($filename, $dossier)
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function getDocumentNumberFromFileNameProvider(): array
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
            'invalid-start-character-is-not-accepted' => [
                'filename' => '*1234.pdf',
                'expectedDocNr' => '',
                'expectException' => true,
            ],
            'invalid-end-character-is-ignored' => [
                'filename' => '1234*.pdf',
                'expectedDocNr' => '1234',
            ],
            'underscore-is-not-accepted' => [
                'filename' => '_.pdf',
                'expectedDocNr' => '',
                'expectException' => true,
            ],
        ];
    }
}
