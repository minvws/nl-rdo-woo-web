<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Search\Index\Dossier;

use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\Type\DossierType;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Search\Index\Dossier\DossierIndexer;
use Shared\Domain\Search\Index\Dossier\Mapper\ElasticDossierMapperInterface;
use Shared\Domain\Search\Index\ElasticDocument;
use Shared\Domain\Search\Index\IndexException;
use Shared\Domain\Search\Index\Updater\NestedDossierIndexUpdater;
use Shared\Service\Elastic\ElasticService;
use Shared\Tests\Unit\UnitTestCase;

class DossierIndexerTest extends UnitTestCase
{
    private ElasticDossierMapperInterface&MockInterface $firstMapper;
    private ElasticDossierMapperInterface&MockInterface $secondMapper;
    private ElasticService&MockInterface $elasticService;
    private NestedDossierIndexUpdater&MockInterface $nestedDossierUpdater;
    private DossierIndexer $indexer;

    protected function setUp(): void
    {
        $this->firstMapper = \Mockery::mock(ElasticDossierMapperInterface::class);
        $this->secondMapper = \Mockery::mock(ElasticDossierMapperInterface::class);

        $this->elasticService = \Mockery::mock(ElasticService::class);
        $this->nestedDossierUpdater = \Mockery::mock(NestedDossierIndexUpdater::class);

        $this->indexer = new DossierIndexer(
            $this->elasticService,
            $this->nestedDossierUpdater,
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

        $this->elasticService->expects('updateDocument')->with($document);
        $this->nestedDossierUpdater->expects('update')->with($dossier, $docValues);

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
