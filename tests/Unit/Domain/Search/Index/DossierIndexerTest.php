<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Search\Index;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Publication\Dossier\Type\DossierType;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Search\Index\Covenant\CovenantMapper;
use App\Domain\Search\Index\DossierIndexer;
use App\Domain\Search\Index\ElasticDocument;
use App\Domain\Search\Index\IndexException;
use App\Domain\Search\Index\WooDecision\WooDecisionMapper;
use App\Service\Elastic\ElasticService;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class DossierIndexerTest extends MockeryTestCase
{
    private CovenantMapper&MockInterface $covenantMapper;
    private WooDecisionMapper&MockInterface $wooDecisionMapper;
    private ElasticService&MockInterface $elasticService;
    private DossierIndexer $indexer;

    public function setUp(): void
    {
        $this->covenantMapper = \Mockery::mock(CovenantMapper::class);
        $this->wooDecisionMapper = \Mockery::mock(WooDecisionMapper::class);
        $this->elasticService = \Mockery::mock(ElasticService::class);

        $this->indexer = new DossierIndexer(
            $this->elasticService,
            $this->covenantMapper,
            $this->wooDecisionMapper
        );
    }

    public function testWooDecisionIndex(): void
    {
        $dossierNr = 'foo-123';
        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('getDossierNr')->andReturn($dossierNr);

        $doc = \Mockery::mock(ElasticDocument::class);
        $doc->shouldReceive('getFieldValues')->andReturn(['foo' => 'bar']);

        $this->wooDecisionMapper->expects('map')->with($dossier)->andReturn($doc);
        $this->elasticService->expects('updateDoc')->with($dossierNr, $doc);
        $this->elasticService->expects('updateAllDocumentsForDossier')->with($dossier, ['foo' => 'bar']);

        $this->indexer->index($dossier);
    }

    public function testCovenantIndex(): void
    {
        $dossierNr = 'foo-123';
        $dossier = \Mockery::mock(Covenant::class);
        $dossier->shouldReceive('getDossierNr')->andReturn($dossierNr);

        $doc = \Mockery::mock(ElasticDocument::class);
        $doc->shouldReceive('getFieldValues')->andReturn(['foo' => 'bar']);

        $this->covenantMapper->expects('map')->with($dossier)->andReturn($doc);
        $this->elasticService->expects('updateDoc')->with($dossierNr, $doc);
        $this->elasticService->expects('updateAllDocumentsForDossier')->with($dossier, ['foo' => 'bar']);

        $this->indexer->index($dossier);
    }

    public function testExceptionIsThrownForUnsupportedType(): void
    {
        $dossierNr = 'foo-123';
        $dossier = \Mockery::mock(AbstractDossier::class);
        $dossier->shouldReceive('getDossierNr')->andReturn($dossierNr);
        $dossier->shouldReceive('getType')->andReturn(DossierType::COVENANT);

        $this->expectExceptionObject(IndexException::forUnsupportedDossierType(DossierType::COVENANT));

        $this->indexer->index($dossier);
    }
}
