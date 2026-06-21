<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Search\Result\SubType\MainDocument;

use MinVWS\TypeArray\TypeArray;
use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\Type\DossierType;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;
use Shared\Domain\Publication\MainDocument\MainDocumentRepository;
use Shared\Domain\Publication\MainDocument\ViewModel\MainDocument;
use Shared\Domain\Publication\MainDocument\ViewModel\MainDocumentViewFactory;
use Shared\Domain\Search\Index\ElasticDocumentType;
use Shared\Domain\Search\Result\SubType\MainDocument\MainDocumentSearchResultMapper;
use Shared\Domain\Search\Result\SubType\SubTypeSearchResultEntry;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DossierTitle;

class MainDocumentSearchResultMapperTest extends UnitTestCase
{
    private MainDocumentRepository&MockInterface $mainDocumentRepository;
    private MainDocumentViewFactory&MockInterface $mainDocumentViewFactory;
    private MainDocumentSearchResultMapper $mapper;

    protected function setUp(): void
    {
        $this->mainDocumentRepository = Mockery::mock(MainDocumentRepository::class);
        $this->mainDocumentViewFactory = Mockery::mock(MainDocumentViewFactory::class);

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
        $hit = Mockery::mock(TypeArray::class);
        $hit->expects('getStringOrNull')->with('[_id]')->andReturnNull();

        $this->assertNull($this->mapper->map($hit));
    }

    public function testMapReturnsNullWhenMainDocumentCannotBeLoaded(): void
    {
        $hit = Mockery::mock(TypeArray::class);
        $hit->expects('getStringOrNull')->with('[_id]')->andReturn('foo');

        $this->mainDocumentRepository->expects('find')->with('foo')->andReturnNull();

        $this->assertNull($this->mapper->map($hit));
    }

    public function testMapSuccessful(): void
    {
        $hit = Mockery::mock(TypeArray::class);
        $hit->expects('getStringOrNull')->with('[_id]')->andReturn('foo');
        $hit->expects('getString')->with('[fields][type][0]')->andReturn(ElasticDocumentType::DISPOSITION_MAIN_DOCUMENT->value);
        $hit->expects('exists')->with('[highlight][pages.content]')->andReturnTrue();
        $hit->expects('getTypeArray->toArray')->andReturn(['x', 'y']);
        $hit->expects('exists')->with('[highlight][dossiers.title]')->andReturnFalse();
        $hit->expects('exists')->with('[highlight][dossiers.summary]')->andReturnFalse();

        $dossier = Mockery::mock(Covenant::class);
        $dossier->expects('getDossierNr')->andReturn($dossierNr = '123');
        $dossier->expects('getDocumentPrefix')->andReturn($documentPrefix = 'foo');
        $dossier->expects('getTitle')->andReturn($title = DossierTitle::create('bar'));
        $dossier->expects('getType')->andReturn($dossierType = DossierType::INVESTIGATION_REPORT);

        $mainDocument = Mockery::mock(AbstractMainDocument::class);
        $mainDocument->expects('getDossier')->andReturn($dossier);

        $viewModel = Mockery::mock(MainDocument::class);

        $this->mainDocumentRepository->expects('find')->with('foo')->andReturn($mainDocument);
        $this->mainDocumentViewFactory->expects('make')->with($dossier, $mainDocument)->andReturn($viewModel);

        $entry = $this->mapper->map($hit);

        self::assertInstanceOf(SubTypeSearchResultEntry::class, $entry);

        $dossierReference = $entry->getDossiers()[0];

        $this->assertInstanceOf(SubTypeSearchResultEntry::class, $entry);
        $this->assertSame($viewModel, $entry->getViewModel());
        $this->assertSame($dossierNr, $dossierReference->getDossierNr());
        $this->assertSame($documentPrefix, $dossierReference->getDocumentPrefix());
        $this->assertSame($dossierType, $dossierReference->getType());
        $this->assertSame($title, $dossierReference->getTitle());
        $this->assertSame(['x', 'y'], $entry->getHighlights());
        $this->assertSame(ElasticDocumentType::DISPOSITION_MAIN_DOCUMENT, $entry->getType());
    }
}
