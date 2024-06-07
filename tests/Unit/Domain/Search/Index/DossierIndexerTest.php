<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Search\Index;

use App\Domain\Publication\Dossier\Type\DossierType;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Search\Index\DossierIndexer;
use App\Domain\Search\Index\ElasticDocument;
use App\Domain\Search\Index\ElasticDossierMapperInterface;
use App\Domain\Search\Index\IndexException;
use App\Service\Elastic\ElasticService;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class DossierIndexerTest extends MockeryTestCase
{
    private ElasticDossierMapperInterface&MockInterface $firstMapper;
    private ElasticDossierMapperInterface&MockInterface $secondMapper;
    private ElasticService&MockInterface $elasticService;
    private DossierIndexer $indexer;

    public function setUp(): void
    {
        $this->firstMapper = \Mockery::mock(ElasticDossierMapperInterface::class);
        $this->secondMapper = \Mockery::mock(ElasticDossierMapperInterface::class);

        $this->elasticService = \Mockery::mock(ElasticService::class);

        $this->indexer = new DossierIndexer(
            $this->elasticService,
            new \ArrayIterator([$this->firstMapper, $this->secondMapper]),
        );
    }

    public function testIndex(): void
    {
        $dossierNr = 'test-123';
        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('getType')->andReturn(DossierType::COVENANT);
        $dossier->shouldReceive('getDossierNr')->andReturn($dossierNr);

        $docValues = ['foo' => 'bar'];

        $document = \Mockery::mock(ElasticDocument::class);
        $document->shouldReceive('getDocumentValues')->andReturn($docValues);

        $this->firstMapper->expects('supports')->with($dossier)->andReturnTrue();
        $this->firstMapper->expects('map')->with($dossier)->andReturn($document);

        $this->elasticService->expects('updateDoc')->with($dossierNr, $document);
        $this->elasticService->expects('updateAllDocumentsForDossier')->with($dossier, $docValues);

        $this->indexer->index($dossier);
    }

    public function testMapThrowsExceptionWhenNoMapperSupportsTheDossier(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('getType')->andReturn(DossierType::COVENANT);

        $this->firstMapper->expects('supports')->with($dossier)->andReturnFalse();
        $this->secondMapper->expects('supports')->with($dossier)->andReturnFalse();

        $this->expectException(IndexException::class);

        $this->indexer->map($dossier);
    }

    public function testMapUsesFirstMapperWhenItSupportsTheDossier(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('getType')->andReturn(DossierType::COVENANT);

        $document = \Mockery::mock(ElasticDocument::class);

        $this->firstMapper->expects('supports')->with($dossier)->andReturnTrue();
        $this->firstMapper->expects('map')->with($dossier)->andReturn($document);

        $this->assertSame(
            $document,
            $this->indexer->map($dossier),
        );
    }

    public function testMapUsesSecondMapperWhenFirstDoesNotSupportTheDossier(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('getType')->andReturn(DossierType::COVENANT);

        $document = \Mockery::mock(ElasticDocument::class);

        $this->firstMapper->expects('supports')->with($dossier)->andReturnFalse();
        $this->secondMapper->expects('supports')->with($dossier)->andReturnTrue();
        $this->secondMapper->expects('map')->with($dossier)->andReturn($document);

        $this->assertSame(
            $document,
            $this->indexer->map($dossier),
        );
    }
}
