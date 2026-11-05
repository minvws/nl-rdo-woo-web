<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Inventory\Sanitizer;

use Doctrine\Common\Collections\ArrayCollection;
use InvalidArgumentException;
use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentUrlGenerator;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Shared\Service\Inventory\Sanitizer\InquiryInventoryDocumentUrlStrategy;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DocumentNumber;
use Shared\ValueObject\Url;

final class InquiryInventoryDocumentUrlStrategyTest extends UnitTestCase
{
    private DocumentUrlGenerator&MockInterface $documentUrlGenerator;
    private WooDecisionRepository&MockInterface $wooDecisionRepository;
    private InquiryInventoryDocumentUrlStrategy $strategy;

    protected function setUp(): void
    {
        $this->documentUrlGenerator = Mockery::mock(DocumentUrlGenerator::class);
        $this->wooDecisionRepository = Mockery::mock(WooDecisionRepository::class);
        $this->strategy = new InquiryInventoryDocumentUrlStrategy(
            $this->documentUrlGenerator,
            $this->wooDecisionRepository,
        );

        parent::setUp();
    }

    public function testGenerateDocumentUrlUsesDossierUrlForSingleDossier(): void
    {
        $dossier = Mockery::mock(WooDecision::class);
        $document = Mockery::mock(Document::class);
        $document->expects('getDossiers')->andReturn(new ArrayCollection([$dossier]));

        $this->documentUrlGenerator
            ->expects('detailUrl')
            ->with($document, $dossier)
            ->andReturn(Url::create('https://example.test/dossier-document'));

        self::assertSame(
            'https://example.test/dossier-document',
            $this->strategy->generateDocumentUrl($document, Mockery::mock(WooDecision::class)),
        );
    }

    public function testGenerateDocumentUrlUsesCanonicalUrlForMultipleDossiers(): void
    {
        $document = Mockery::mock(Document::class);
        $document->expects('getDossiers')->andReturn(new ArrayCollection([
            Mockery::mock(WooDecision::class),
            Mockery::mock(WooDecision::class),
        ]));
        $document->expects('getDocumentNumber')
            ->andReturn($documentNumber = DocumentNumber::fromString('prefix-matter-123'));

        $this->documentUrlGenerator
            ->expects('canonicalUrl')
            ->with($documentNumber)
            ->andReturn(Url::create('http://foo.bar/document/prefix-matter-123'));

        self::assertSame(
            'http://foo.bar/document/prefix-matter-123',
            $this->strategy->generateDocumentUrl($document, Mockery::mock(WooDecision::class)),
        );
    }

    public function testGenerateDocumentUrlFailsWithoutDossier(): void
    {
        $document = Mockery::mock(Document::class);
        $document->expects('getDossiers')->andReturn(new ArrayCollection());

        $this->expectException(InvalidArgumentException::class);

        $this->strategy->generateDocumentUrl($document, Mockery::mock(WooDecision::class));
    }

    public function testGenerateRelatedDocumentUrlIsEmptyWithoutPublicDossier(): void
    {
        $document = Mockery::mock(Document::class);
        $document->expects('getDocumentNumber')
            ->andReturn($documentNumber = DocumentNumber::fromString('prefix-matter-123'));
        $this->wooDecisionRepository
            ->expects('hasPubliclyAvailableDossierForDocument')
            ->with($documentNumber)
            ->andReturnFalse();

        self::assertSame(
            '',
            $this->strategy->generateRelatedDocumentUrl($document, Mockery::mock(WooDecision::class)),
        );
    }

    public function testGenerateRelatedDocumentUrlUsesDossierUrlForSinglePublicDossier(): void
    {
        $dossier = Mockery::mock(WooDecision::class);
        $document = Mockery::mock(Document::class);
        $document->expects('getDocumentNumber')
            ->andReturn($documentNumber = DocumentNumber::fromString('prefix-matter-123'));
        $document->expects('getDossiers')->andReturn(new ArrayCollection([$dossier]));

        $this->wooDecisionRepository
            ->expects('hasPubliclyAvailableDossierForDocument')
            ->with($documentNumber)
            ->andReturnTrue();
        $this->documentUrlGenerator
            ->expects('detailUrl')
            ->with($document, $dossier)
            ->andReturn(Url::create('https://example.test/dossier-document'));

        self::assertSame(
            'https://example.test/dossier-document',
            $this->strategy->generateRelatedDocumentUrl($document, Mockery::mock(WooDecision::class)),
        );
    }

    public function testGenerateRelatedDocumentUrlUsesCanonicalUrlForMultipleDossiers(): void
    {
        $document = Mockery::mock(Document::class);
        $document->expects('getDocumentNumber')
            ->twice()
            ->andReturn($documentNumber = DocumentNumber::fromString('prefix-matter-123'));
        $document->expects('getDossiers')->andReturn(new ArrayCollection([
            Mockery::mock(WooDecision::class),
            Mockery::mock(WooDecision::class),
        ]));

        $this->wooDecisionRepository
            ->expects('hasPubliclyAvailableDossierForDocument')
            ->with($documentNumber)
            ->andReturnTrue();
        $this->documentUrlGenerator
            ->expects('canonicalUrl')
            ->with($documentNumber)
            ->andReturn(Url::create('http://foo.bar/document/prefix-matter-123'));

        self::assertSame(
            'http://foo.bar/document/prefix-matter-123',
            $this->strategy->generateRelatedDocumentUrl($document, Mockery::mock(WooDecision::class)),
        );
    }
}
