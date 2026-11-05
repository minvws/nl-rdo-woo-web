<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Inventory\Sanitizer;

use Doctrine\Common\Collections\ArrayCollection;
use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentUrlGenerator;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Shared\Service\Inventory\Sanitizer\DossierInventoryDocumentUrlStrategy;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DocumentNumber;
use Shared\ValueObject\Url;

final class DossierInventoryDocumentUrlStrategyTest extends UnitTestCase
{
    private WooDecisionRepository&MockInterface $wooDecisionRepository;
    private DocumentUrlGenerator&MockInterface $documentUrlGenerator;
    private DossierInventoryDocumentUrlStrategy $strategy;

    protected function setUp(): void
    {
        $this->wooDecisionRepository = Mockery::mock(WooDecisionRepository::class);
        $this->documentUrlGenerator = Mockery::mock(DocumentUrlGenerator::class);
        $this->strategy = new DossierInventoryDocumentUrlStrategy(
            $this->wooDecisionRepository,
            $this->documentUrlGenerator,
        );

        parent::setUp();
    }

    public function testGenerateDocumentUrlUsesInventoryDossier(): void
    {
        $dossier = Mockery::mock(WooDecision::class);
        $document = Mockery::mock(Document::class);
        $this->documentUrlGenerator
            ->expects('detailUrl')
            ->with($document, $dossier)
            ->andReturn(Url::create('http://foo.bar/document/PREFIX/dossier-123/document/prefix-matter-123'));

        self::assertSame(
            'http://foo.bar/document/PREFIX/dossier-123/document/prefix-matter-123',
            $this->strategy->generateDocumentUrl($document, $dossier),
        );
    }

    public function testGenerateRelatedDocumentUrlUsesInventoryDossierWhenDocumentBelongsToIt(): void
    {
        $dossier = Mockery::mock(WooDecision::class);
        $document = Mockery::mock(Document::class);
        $document->expects('getDossiers')->andReturn(new ArrayCollection([$dossier]));
        $this->documentUrlGenerator
            ->expects('detailUrl')
            ->with($document, $dossier)
            ->andReturn(Url::create('http://foo.bar/document/PREFIX/dossier-123/document/prefix-matter-123'));

        self::assertSame(
            'http://foo.bar/document/PREFIX/dossier-123/document/prefix-matter-123',
            $this->strategy->generateRelatedDocumentUrl($document, $dossier),
        );
    }

    public function testGenerateRelatedDocumentUrlUsesCanonicalUrlForOtherPublishedDossier(): void
    {
        $dossier = Mockery::mock(WooDecision::class);
        $document = Mockery::mock(Document::class);
        $document->expects('getDossiers')->andReturn(new ArrayCollection());
        $document->expects('getDocumentNumber')
            ->twice()
            ->andReturn($documentNumber = DocumentNumber::fromString('prefix-matter-123'));

        $this->wooDecisionRepository
            ->expects('hasPublishedDossierForDocumentExcept')
            ->with($documentNumber, $dossier)
            ->andReturnTrue();
        $this->documentUrlGenerator
            ->expects('canonicalUrl')
            ->with($documentNumber)
            ->andReturn(Url::create('http://foo.bar/document/prefix-matter-123'));

        self::assertSame(
            'http://foo.bar/document/prefix-matter-123',
            $this->strategy->generateRelatedDocumentUrl($document, $dossier),
        );
    }

    public function testGenerateRelatedDocumentUrlIsEmptyWithoutOtherPublishedDossier(): void
    {
        $dossier = Mockery::mock(WooDecision::class);
        $document = Mockery::mock(Document::class);
        $document->expects('getDossiers')->andReturn(new ArrayCollection());
        $document->expects('getDocumentNumber')
            ->once()
            ->andReturn($documentNumber = DocumentNumber::fromString('prefix-matter-123'));

        $this->wooDecisionRepository
            ->expects('hasPublishedDossierForDocumentExcept')
            ->with($documentNumber, $dossier)
            ->andReturnFalse();

        self::assertSame(
            '',
            $this->strategy->generateRelatedDocumentUrl($document, $dossier),
        );
    }
}
