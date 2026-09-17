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
use Shared\ValueObject\DocumentNumber;

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
        $hit->expects('getStringOrNull')->with('[fields][document_number][0]')->andReturnNull();

        $this->assertNull($this->mapper->map($hit));
    }

    public function testMapReturnsNullWhenViewModelCannotBeLoaded(): void
    {
        $documentNumber = DocumentNumber::fromString('Prefix-Matter_01-Doc.123');
        $hit = Mockery::mock(TypeArray::class);
        $hit->expects('getStringOrNull')->with('[fields][document_number][0]')->andReturn($documentNumber->toString());

        $this->documentRepository
            ->expects('getDocumentSearchEntry')
            ->with(Mockery::on(static function (DocumentNumber $value) use ($documentNumber): bool {
                self::assertSame($documentNumber->toString(), $value->toString());

                return true;
            }))
            ->andReturnNull();

        $this->assertNull($this->mapper->map($hit));
    }

    public function testMapSuccessful(): void
    {
        $documentNumber = DocumentNumber::fromString('Prefix-Matter_01-Doc.123');
        $capturedDocumentNumber = null;
        $hit = Mockery::mock(TypeArray::class);
        $hit->expects('getStringOrNull')->with('[fields][document_number][0]')->andReturn($documentNumber->toString());
        $hit->expects('exists')->with('[highlight][pages.content]')->andReturnTrue();
        $hit->expects('getTypeArray->toArray')->andReturn(['x', 'y']);
        $hit->expects('exists')->with('[highlight][dossiers.title]')->andReturnFalse();
        $hit->expects('exists')->with('[highlight][dossiers.summary]')->andReturnFalse();

        $viewModel = Mockery::mock(DocumentViewModel::class);
        $dossierReference = Mockery::mock(DossierReference::class);

        $this->documentRepository
            ->expects('getDocumentSearchEntry')
            ->with(Mockery::on(static function (DocumentNumber $value) use (&$capturedDocumentNumber, $documentNumber): bool {
                self::assertSame($documentNumber->toString(), $value->toString());
                $capturedDocumentNumber = $value;

                return true;
            }))
            ->andReturn($viewModel);
        $this->dossierRepository
            ->expects('getDossierReferencesForDocument')
            ->with(Mockery::on(static function (DocumentNumber $value) use (&$capturedDocumentNumber, $documentNumber): bool {
                self::assertNotNull($capturedDocumentNumber);
                self::assertSame($capturedDocumentNumber, $value);
                self::assertSame($documentNumber->toString(), $value->toString());

                return true;
            }))
            ->andReturn([$dossierReference]);

        $entry = $this->mapper->map($hit);

        $this->assertInstanceOf(SubTypeSearchResultEntry::class, $entry);
        $this->assertSame($viewModel, $entry->getViewModel());
        $this->assertSame([$dossierReference], $entry->getDossiers());
        $this->assertSame(['x', 'y'], $entry->getHighlights());
        $this->assertSame(ElasticDocumentType::WOO_DECISION_DOCUMENT, $entry->getType());
    }
}
