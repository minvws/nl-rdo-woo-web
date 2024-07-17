<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Search\Result\SubType\MainDocument;

use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Publication\MainDocument\AbstractMainDocument;
use App\Domain\Publication\MainDocument\AbstractMainDocumentRepository;
use App\Domain\Publication\MainDocument\ViewModel\MainDocument;
use App\Domain\Publication\MainDocument\ViewModel\MainDocumentViewFactory;
use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Result\SubType\MainDocument\MainDocumentSearchResultMapper;
use App\Domain\Search\Result\SubType\SubTypeSearchResultEntry;
use Jaytaph\TypeArray\TypeArray;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class MainDocumentSearchResultMapperTest extends MockeryTestCase
{
    private AbstractMainDocumentRepository&MockInterface $mainDocumentRepository;
    private MainDocumentViewFactory&MockInterface $mainDocumentViewFactory;
    private MainDocumentSearchResultMapper $mapper;

    public function setUp(): void
    {
        $this->mainDocumentRepository = \Mockery::mock(AbstractMainDocumentRepository::class);
        $this->mainDocumentViewFactory = \Mockery::mock(MainDocumentViewFactory::class);

        $this->mapper = new MainDocumentSearchResultMapper(
            $this->mainDocumentRepository,
            $this->mainDocumentViewFactory,
        );
    }

    public function testSupports(): void
    {
        self::assertTrue($this->mapper->supports(ElasticDocumentType::COMPLAINT_JUDGEMENT_MAIN_DOCUMENT));
        self::assertFalse($this->mapper->supports(ElasticDocumentType::WOO_DECISION_DOCUMENT));
    }

    public function testMapReturnsNullWhenIdIsMissing(): void
    {
        $hit = \Mockery::mock(TypeArray::class);
        $hit->shouldReceive('getStringOrNull')->with('[_id]')->andReturnNull();

        $this->assertNull($this->mapper->map($hit));
    }

    public function testMapReturnsNullWhenMainDocumentCannotBeLoaded(): void
    {
        $hit = \Mockery::mock(TypeArray::class);
        $hit->shouldReceive('getStringOrNull')->with('[_id]')->andReturn('foo');

        $this->mainDocumentRepository->shouldReceive('find')->with('foo')->andReturnNull();

        $this->assertNull($this->mapper->map($hit));
    }

    public function testMapSuccessful(): void
    {
        $hit = \Mockery::mock(TypeArray::class);
        $hit->shouldReceive('getStringOrNull')->with('[_id]')->andReturn('foo');
        $hit->shouldReceive('getString')->with('[fields][type][0]')->andReturn(ElasticDocumentType::DISPOSITION_MAIN_DOCUMENT->value);
        $hit->shouldReceive('exists')->with('[highlight][pages.content]')->andReturnTrue();
        $hit->shouldReceive('getTypeArray->toArray')->andReturn(['x', 'y']);
        $hit->shouldReceive('exists')->with('[highlight][dossiers.title]')->andReturnFalse();
        $hit->shouldReceive('exists')->with('[highlight][dossiers.summary]')->andReturnFalse();

        $dossier = \Mockery::mock(Covenant::class);
        $dossier->shouldReceive('getDossierNr')->andReturn($dossierNr = '123');
        $dossier->shouldReceive('getDocumentPrefix')->andReturn($documentPrefix = 'foo');
        $dossier->shouldReceive('getTitle')->andReturn($title = 'bar');

        $mainDocument = \Mockery::mock(AbstractMainDocument::class);
        $mainDocument->shouldReceive('getDossier')->andReturn($dossier);

        /** @var MainDocument&MockInterface $viewModel */
        $viewModel = \Mockery::mock(MainDocument::class);

        $this->mainDocumentRepository->shouldReceive('find')->with('foo')->andReturn($mainDocument);
        $this->mainDocumentViewFactory->expects('make')->with($dossier, $mainDocument)->andReturn($viewModel);

        $entry = $this->mapper->map($hit);

        self::assertInstanceOf(SubTypeSearchResultEntry::class, $entry);

        $dossierReference = $entry->getDossiers()[0];

        $this->assertInstanceOf(SubTypeSearchResultEntry::class, $entry);
        $this->assertSame($viewModel, $entry->getViewModel());
        $this->assertSame($dossierNr, $dossierReference->getDossierNr());
        $this->assertSame($documentPrefix, $dossierReference->getDocumentPrefix());
        $this->assertSame($title, $dossierReference->getTitle());
        $this->assertSame(['x', 'y'], $entry->getHighlights());
        $this->assertSame(ElasticDocumentType::DISPOSITION_MAIN_DOCUMENT, $entry->getType());
    }
}
