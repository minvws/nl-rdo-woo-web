<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Search\Result\Covenant;

use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Result\Covenant\CovenantResultMapper;
use App\Domain\Search\Result\MainTypeEntry;
use App\Repository\CovenantRepository;
use App\ViewModel\CovenantSearchEntry;
use Jaytaph\TypeArray\TypeArray;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class CovenantResultMapperTest extends MockeryTestCase
{
    private CovenantRepository&MockInterface $covenantRepository;
    private CovenantResultMapper $mapper;

    public function setUp(): void
    {
        $this->covenantRepository = \Mockery::mock(CovenantRepository::class);

        $this->mapper = new CovenantResultMapper(
            $this->covenantRepository,
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

        $this->covenantRepository->shouldReceive('getSearchEntry')->with('foo', 'bar')->andReturnNull();

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

        $viewModel = \Mockery::mock(CovenantSearchEntry::class);

        $this->covenantRepository->shouldReceive('getSearchEntry')->with('foo', 'bar')->andReturn($viewModel);

        $entry = $this->mapper->map($hit);

        $this->assertInstanceOf(MainTypeEntry::class, $entry);
        $this->assertSame($viewModel, $entry->getDossier());
        $this->assertSame(['x', 'y'], $entry->getHighlights());
        $this->assertSame(ElasticDocumentType::COVENANT, $entry->getType());
    }
}
