<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Search\Result\Dossier;

use MinVWS\TypeArray\TypeArray;
use Mockery\MockInterface;
use Shared\Domain\Search\Index\ElasticDocumentType;
use Shared\Domain\Search\Result\Dossier\Covenant\CovenantSearchResult;
use Shared\Domain\Search\Result\Dossier\DossierSearchResultBaseMapper;
use Shared\Domain\Search\Result\Dossier\DossierSearchResultEntry;
use Shared\Domain\Search\Result\Dossier\ProvidesDossierTypeSearchResultInterface;
use Shared\Service\Security\ApplicationMode\ApplicationMode;
use Shared\Tests\Unit\UnitTestCase;

class DossierSearchResultBaseMapperTest extends UnitTestCase
{
    private DossierSearchResultBaseMapper $mapper;
    private ProvidesDossierTypeSearchResultInterface&MockInterface $repository;
    private TypeArray&MockInterface $hit;

    protected function setUp(): void
    {
        $this->repository = \Mockery::mock(ProvidesDossierTypeSearchResultInterface::class);
        $this->hit = \Mockery::mock(TypeArray::class);

        $this->mapper = new DossierSearchResultBaseMapper();
    }

    public function testMapReturnsNullWhenPrefixIsMissing(): void
    {
        $this->hit->shouldReceive('getStringOrNull')->with('[fields][document_prefix][0]')->andReturnNull();
        $this->hit->shouldReceive('getStringOrNull')->with('[fields][dossier_nr][0]')->andReturnNull();

        $this->assertNull($this->mapper->map($this->hit, $this->repository, ElasticDocumentType::ANNUAL_REPORT));
    }

    public function testMapReturnsNullWhenViewModelCannotBeLoaded(): void
    {
        $this->hit->shouldReceive('getStringOrNull')->with('[fields][document_prefix][0]')->andReturn('foo');
        $this->hit->shouldReceive('getStringOrNull')->with('[fields][dossier_nr][0]')->andReturn('bar');

        $this->repository
            ->shouldReceive('getSearchResultViewModel')
            ->with('foo', 'bar', ApplicationMode::ADMIN)
            ->andReturnNull();

        $this->assertNull(
            $this->mapper->map(
                $this->hit,
                $this->repository,
                ElasticDocumentType::ANNUAL_REPORT,
                [],
                ApplicationMode::ADMIN,
            ),
        );
    }

    public function testMapSuccessful(): void
    {
        $this->hit = \Mockery::mock(TypeArray::class);
        $this->hit->shouldReceive('getStringOrNull')->with('[fields][document_prefix][0]')->andReturn('foo');
        $this->hit->shouldReceive('getStringOrNull')->with('[fields][dossier_nr][0]')->andReturn('bar');
        $this->hit->shouldReceive('exists')->with('[highlight][title]')->andReturnTrue();
        $this->hit->shouldReceive('getTypeArray->toArray')->andReturn(['x', 'y']);
        $this->hit->shouldReceive('exists')->with('[highlight][summary]')->andReturnFalse();

        $viewModel = \Mockery::mock(CovenantSearchResult::class);

        $this->repository
            ->shouldReceive('getSearchResultViewModel')
            ->with('foo', 'bar', ApplicationMode::PUBLIC)
            ->andReturn($viewModel);

        $entry = $this->mapper->map($this->hit, $this->repository, ElasticDocumentType::COVENANT);

        $this->assertInstanceOf(DossierSearchResultEntry::class, $entry);
        $this->assertSame($viewModel, $entry->getDossier());
        $this->assertSame(['x', 'y'], $entry->getHighlights());
        $this->assertSame(ElasticDocumentType::COVENANT, $entry->getType());
    }
}
