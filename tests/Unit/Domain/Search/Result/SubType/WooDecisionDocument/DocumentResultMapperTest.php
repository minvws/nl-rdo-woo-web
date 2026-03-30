<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Search\Result\SubType\WooDecisionDocument;

use MinVWS\TypeArray\TypeArray;
use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\Type\DossierReference;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Shared\Domain\Search\Index\ElasticDocumentType;
use Shared\Domain\Search\Result\SubType\SubTypeSearchResultEntry;
use Shared\Domain\Search\Result\SubType\WooDecisionDocument\DocumentSearchResultMapper;
use Shared\Domain\Search\Result\SubType\WooDecisionDocument\DocumentViewModel;
use Shared\Tests\Unit\UnitTestCase;

class DocumentResultMapperTest extends UnitTestCase
{
    private DocumentRepository&MockInterface $documentRepository;
    private WooDecisionRepository&MockInterface $dossierRepository;
    private DocumentSearchResultMapper $mapper;

    protected function setUp(): void
    {
        $this->documentRepository = Mockery::mock(DocumentRepository::class);
        $this->dossierRepository = Mockery::mock(WooDecisionRepository::class);

        $this->mapper = new DocumentSearchResultMapper(
            $this->documentRepository,
            $this->dossierRepository,
        );
    }

    public function testMapReturnsNullWhenPrefixIsMissing(): void
    {
        $hit = Mockery::mock(TypeArray::class);
        $hit->expects('getStringOrNull')->with('[fields][document_nr][0]')->andReturnNull();

        $this->assertNull($this->mapper->map($hit));
    }

    public function testMapReturnsNullWhenViewModelCannotBeLoaded(): void
    {
        $hit = Mockery::mock(TypeArray::class);
        $hit->expects('getStringOrNull')->with('[fields][document_nr][0]')->andReturn('foo');

        $this->documentRepository->expects('getDocumentSearchEntry')->with('foo')->andReturnNull();

        $this->assertNull($this->mapper->map($hit));
    }

    public function testMapSuccessful(): void
    {
        $hit = Mockery::mock(TypeArray::class);
        $hit->expects('getStringOrNull')->with('[fields][document_nr][0]')->andReturn('foo');
        $hit->expects('exists')->with('[highlight][pages.content]')->andReturnTrue();
        $hit->expects('getTypeArray->toArray')->andReturn(['x', 'y']);
        $hit->expects('exists')->with('[highlight][dossiers.title]')->andReturnFalse();
        $hit->expects('exists')->with('[highlight][dossiers.summary]')->andReturnFalse();

        $viewModel = Mockery::mock(DocumentViewModel::class);
        $dossierReference = Mockery::mock(DossierReference::class);

        $this->documentRepository->expects('getDocumentSearchEntry')->with('foo')->andReturn($viewModel);
        $this->dossierRepository->expects('getDossierReferencesForDocument')->with('foo')->andReturn([$dossierReference]);

        $entry = $this->mapper->map($hit);

        $this->assertInstanceOf(SubTypeSearchResultEntry::class, $entry);
        $this->assertSame($viewModel, $entry->getViewModel());
        $this->assertSame([$dossierReference], $entry->getDossiers());
        $this->assertSame(['x', 'y'], $entry->getHighlights());
        $this->assertSame(ElasticDocumentType::WOO_DECISION_DOCUMENT, $entry->getType());
    }
}
