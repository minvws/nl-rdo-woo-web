<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Search\Result\WooDecision;

use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Result\MainTypeEntry;
use App\Domain\Search\Result\WooDecision\WooDecisionResultMapper;
use App\Repository\DossierRepository;
use App\ViewModel\DossierSearchEntry;
use Jaytaph\TypeArray\TypeArray;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class WooDecisionResultMapperTest extends MockeryTestCase
{
    private DossierRepository&MockInterface $dossierRepository;
    private WooDecisionResultMapper $mapper;

    public function setUp(): void
    {
        $this->dossierRepository = \Mockery::mock(DossierRepository::class);

        $this->mapper = new WooDecisionResultMapper(
            $this->dossierRepository,
        );
    }

    public function testMapReturnsNullWhenPrefixIsMissing(): void
    {
        $hit = \Mockery::mock(TypeArray::class);
        $hit->shouldReceive('getStringOrNull')->with('[fields][document_prefix][0]')->andReturnNull();
        $hit->shouldReceive('getStringOrNull')->with('[fields][dossier_nr][0]')->andReturnNull();

        $this->assertNull($this->mapper->map($hit));
    }

    public function testMapReturnsNullWhenViewModelCannotBeLoaded(): void
    {
        $hit = \Mockery::mock(TypeArray::class);
        $hit->shouldReceive('getStringOrNull')->with('[fields][document_prefix][0]')->andReturn('foo');
        $hit->shouldReceive('getStringOrNull')->with('[fields][dossier_nr][0]')->andReturn('bar');

        $this->dossierRepository->shouldReceive('getDossierSearchEntry')->with('foo', 'bar')->andReturnNull();

        $this->assertNull($this->mapper->map($hit));
    }

    public function testMapSuccessful(): void
    {
        $hit = \Mockery::mock(TypeArray::class);
        $hit->shouldReceive('getStringOrNull')->with('[fields][document_prefix][0]')->andReturn('foo');
        $hit->shouldReceive('getStringOrNull')->with('[fields][dossier_nr][0]')->andReturn('bar');
        $hit->shouldReceive('exists')->with('[highlight][title]')->andReturnTrue();
        $hit->shouldReceive('getTypeArray->toArray')->andReturn(['x', 'y']);
        $hit->shouldReceive('exists')->with('[highlight][summary]')->andReturnFalse();
        $hit->shouldReceive('exists')->with('[highlight][decision_content]')->andReturnFalse();

        $viewModel = \Mockery::mock(DossierSearchEntry::class);

        $this->dossierRepository->shouldReceive('getDossierSearchEntry')->with('foo', 'bar')->andReturn($viewModel);

        $entry = $this->mapper->map($hit);

        $this->assertInstanceOf(MainTypeEntry::class, $entry);
        $this->assertSame($viewModel, $entry->getDossier());
        $this->assertSame(['x', 'y'], $entry->getHighlights());
        $this->assertSame(ElasticDocumentType::WOO_DECISION, $entry->getType());
    }
}
