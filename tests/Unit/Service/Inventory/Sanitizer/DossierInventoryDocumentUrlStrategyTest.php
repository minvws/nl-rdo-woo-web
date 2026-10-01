<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Inventory\Sanitizer;

use Doctrine\Common\Collections\ArrayCollection;
use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Shared\Domain\Publication\PublicUrlGenerator;
use Shared\Service\DocumentCanonicalUrlGenerator;
use Shared\Service\DocumentDossierUrlGenerator;
use Shared\Service\Inventory\Sanitizer\DossierInventoryDocumentUrlStrategy;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DocumentNumber;
use Shared\ValueObject\Url;

final class DossierInventoryDocumentUrlStrategyTest extends UnitTestCase
{
    private WooDecisionRepository&MockInterface $wooDecisionRepository;
    private DocumentDossierUrlGenerator&MockInterface $documentDossierUrlGenerator;
    private PublicUrlGenerator&MockInterface $publicUrlGenerator;
    private DossierInventoryDocumentUrlStrategy $strategy;

    protected function setUp(): void
    {
        $this->wooDecisionRepository = Mockery::mock(WooDecisionRepository::class);
        $this->documentDossierUrlGenerator = Mockery::mock(DocumentDossierUrlGenerator::class);
        $this->publicUrlGenerator = Mockery::mock(PublicUrlGenerator::class);
        $this->strategy = new DossierInventoryDocumentUrlStrategy(
            $this->wooDecisionRepository,
            $this->documentDossierUrlGenerator,
            new DocumentCanonicalUrlGenerator($this->publicUrlGenerator),
        );

        parent::setUp();
    }

    public function testGenerateDocumentUrlUsesInventoryDossier(): void
    {
        $dossier = Mockery::mock(WooDecision::class);
        $document = Mockery::mock(Document::class);
        $this->documentDossierUrlGenerator
            ->expects('dossier')
            ->with($document, $dossier)
            ->andReturn('http://foo.bar/document/PREFIX/dossier-123/document/PREFIX-matter-123');

        self::assertSame(
            'http://foo.bar/document/PREFIX/dossier-123/document/PREFIX-matter-123',
            $this->strategy->generateDocumentUrl($document, $dossier),
        );
    }

    public function testGenerateRelatedDocumentUrlUsesInventoryDossierWhenDocumentBelongsToIt(): void
    {
        $dossier = Mockery::mock(WooDecision::class);
        $document = Mockery::mock(Document::class);
        $document->expects('getDossiers')->andReturn(new ArrayCollection([$dossier]));
        $this->documentDossierUrlGenerator
            ->expects('dossier')
            ->with($document, $dossier)
            ->andReturn('http://foo.bar/document/PREFIX/dossier-123/document/PREFIX-matter-123');

        self::assertSame(
            'http://foo.bar/document/PREFIX/dossier-123/document/PREFIX-matter-123',
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
            ->andReturn($documentNumber = DocumentNumber::fromString('PREFIX-matter-123'));

        $this->wooDecisionRepository
            ->expects('hasPublishedDossierForDocumentExcept')
            ->with($documentNumber, $dossier)
            ->andReturnTrue();
        $this->publicUrlGenerator
            ->expects('buildUrlFromRoute')
            ->with('app_document_canonical', ['documentNumber' => $documentNumber->toString()])
            ->andReturn(Url::create('http://foo.bar/document/PREFIX-matter-123'));

        self::assertSame(
            'http://foo.bar/document/PREFIX-matter-123',
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
            ->andReturn($documentNumber = DocumentNumber::fromString('PREFIX-matter-123'));

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
