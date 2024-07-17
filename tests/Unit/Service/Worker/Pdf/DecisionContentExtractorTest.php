<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Worker\Pdf;

use App\Entity\DecisionDocument;
use App\Entity\Dossier;
use App\Service\Elastic\ElasticService;
use App\Service\Stats\WorkerStatsService;
use App\Service\Storage\EntityStorageService;
use App\Service\Worker\Pdf\Extractor\DecisionContentExtractor;
use App\Service\Worker\Pdf\Tools\TesseractService;
use App\Service\Worker\Pdf\Tools\TikaService;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Symfony\Component\Uid\Uuid;

final class DecisionContentExtractorTest extends UnitTestCase
{
    protected EntityStorageService&MockInterface $entityStorageService;
    protected TesseractService&MockInterface $tesseract;
    protected TikaService&MockInterface $tika;
    protected ElasticService&MockInterface $elasticService;
    protected WorkerStatsService&MockInterface $statsService;
    protected Dossier&MockInterface $dossier;
    protected DecisionDocument&MockInterface $decisionDocument;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityStorageService = \Mockery::mock(EntityStorageService::class);
        $this->tesseract = \Mockery::mock(TesseractService::class);
        $this->tika = \Mockery::mock(TikaService::class);
        $this->statsService = \Mockery::mock(WorkerStatsService::class);
        $this->elasticService = \Mockery::mock(ElasticService::class);
        $this->dossier = \Mockery::mock(Dossier::class);
        $this->decisionDocument = \Mockery::mock(DecisionDocument::class);
    }

    public function testExtract(): void
    {
        $this->entityStorageService
            ->shouldReceive('downloadEntity')
            ->once()
            ->with($this->decisionDocument)
            ->andReturn($localPath = 'localPath');

        $this->statsService
            ->shouldReceive('measure')
            ->once()
            ->with('download.document', \Mockery::on(function (\Closure $closure) use ($localPath) {
                $result = $closure();

                $this->assertSame($localPath, $result);

                return true;
            }))
            ->andReturn($localPath);

        $this->tika
            ->shouldReceive('extract')
            ->once()
            ->with($localPath)
            ->andReturn($tikaResult = ['X-TIKA:content' => 'lorem ipsum tika', 'name' => 'acme']);

        $this->statsService
            ->shouldReceive('measure')
            ->once()
            ->with('tika', \Mockery::on(function (\Closure $closure) use ($tikaResult) {
                $result = $closure();

                $this->assertSame($tikaResult, $result);

                return true;
            }))
            ->andReturn($tikaResult);

        $this->tesseract
            ->shouldReceive('extract')
            ->once()
            ->with($localPath)
            ->andReturn($tesseractResult = 'lorem ipsum tesseract');

        $this->statsService
            ->shouldReceive('measure')
            ->once()
            ->with('tesseract', \Mockery::on(function (\Closure $closure) use ($tesseractResult) {
                $result = $closure();

                $this->assertSame($tesseractResult, $result);

                return true;
            }))
            ->andReturn($tesseractResult);

        $this->entityStorageService
            ->shouldReceive('removeDownload')
            ->once()
            ->with($localPath);

        $this->elasticService
            ->shouldReceive('updateDossierDecisionContent')
            ->once()
            ->with($this->dossier, "lorem ipsum tika\nlorem ipsum tesseract");

        $this->getExtractor()->extract($this->dossier, $this->decisionDocument, false);
    }

    public function testExtractWhenDownloadingDocumentFails(): void
    {
        $this->entityStorageService
            ->shouldReceive('downloadEntity')
            ->once()
            ->with($this->decisionDocument)
            ->andReturnFalse();

        $this->statsService
            ->shouldReceive('measure')
            ->once()
            ->with('download.document', \Mockery::on(function (\Closure $closure) {
                $result = $closure();

                $this->assertFalse($result);

                return true;
            }))
            ->andReturnFalse();

        $this->decisionDocument
            ->shouldReceive('getId')
            ->once()
            ->andReturn($decisionDocumentId = Uuid::v6());

        $this->expectExceptionObject(new \RuntimeException('Failed to file to local storage for DecisionDocument ' . $decisionDocumentId->toBase58()));

        $this->getExtractor()->extract($this->dossier, $this->decisionDocument, false);
    }

    private function getExtractor(): DecisionContentExtractor
    {
        return new DecisionContentExtractor(
            $this->entityStorageService,
            $this->tesseract,
            $this->tika,
            $this->elasticService,
            $this->statsService,
        );
    }
}
