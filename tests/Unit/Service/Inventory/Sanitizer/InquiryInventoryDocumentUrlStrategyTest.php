<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Inventory\Sanitizer;

use Doctrine\Common\Collections\ArrayCollection;
use InvalidArgumentException;
use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Shared\Domain\Publication\PublicUrlGenerator;
use Shared\Service\DocumentCanonicalUrlGenerator;
use Shared\Service\DocumentDossierUrlGenerator;
use Shared\Service\Inventory\Sanitizer\InquiryInventoryDocumentUrlStrategy;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DocumentNumber;
use Shared\ValueObject\Url;

final class InquiryInventoryDocumentUrlStrategyTest extends UnitTestCase
{
    private DocumentDossierUrlGenerator&MockInterface $documentDossierUrlGenerator;
    private WooDecisionRepository&MockInterface $wooDecisionRepository;
    private PublicUrlGenerator&MockInterface $publicUrlGenerator;
    private InquiryInventoryDocumentUrlStrategy $strategy;

    protected function setUp(): void
    {
        $this->documentDossierUrlGenerator = Mockery::mock(DocumentDossierUrlGenerator::class);
        $this->wooDecisionRepository = Mockery::mock(WooDecisionRepository::class);
        $this->publicUrlGenerator = Mockery::mock(PublicUrlGenerator::class);
        $this->strategy = new InquiryInventoryDocumentUrlStrategy(
            $this->documentDossierUrlGenerator,
            $this->wooDecisionRepository,
            new DocumentCanonicalUrlGenerator($this->publicUrlGenerator),
        );

        parent::setUp();
    }

    public function testGenerateDocumentUrlUsesDossierUrlForSingleDossier(): void
    {
        $dossier = Mockery::mock(WooDecision::class);
        $document = Mockery::mock(Document::class);
        $document->expects('getDossiers')->andReturn(new ArrayCollection([$dossier]));

        $this->documentDossierUrlGenerator
            ->expects('dossier')
            ->with($document, $dossier)
            ->andReturn('https://example.test/dossier-document');

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
            ->andReturn($documentNumber = DocumentNumber::fromString('PREFIX-matter-123'));

        $this->publicUrlGenerator
            ->expects('buildUrlFromRoute')
            ->with('app_document_canonical', ['documentNumber' => $documentNumber->toString()])
            ->andReturn(Url::create('http://foo.bar/document/PREFIX-matter-123'));

        self::assertSame(
            'http://foo.bar/document/PREFIX-matter-123',
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
            ->andReturn($documentNumber = DocumentNumber::fromString('PREFIX-matter-123'));
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
            ->andReturn($documentNumber = DocumentNumber::fromString('PREFIX-matter-123'));
        $document->expects('getDossiers')->andReturn(new ArrayCollection([$dossier]));

        $this->wooDecisionRepository
            ->expects('hasPubliclyAvailableDossierForDocument')
            ->with($documentNumber)
            ->andReturnTrue();
        $this->documentDossierUrlGenerator
            ->expects('dossier')
            ->with($document, $dossier)
            ->andReturn('https://example.test/dossier-document');

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
            ->andReturn($documentNumber = DocumentNumber::fromString('PREFIX-matter-123'));
        $document->expects('getDossiers')->andReturn(new ArrayCollection([
            Mockery::mock(WooDecision::class),
            Mockery::mock(WooDecision::class),
        ]));

        $this->wooDecisionRepository
            ->expects('hasPubliclyAvailableDossierForDocument')
            ->with($documentNumber)
            ->andReturnTrue();
        $this->publicUrlGenerator
            ->expects('buildUrlFromRoute')
            ->with('app_document_canonical', ['documentNumber' => $documentNumber->toString()])
            ->andReturn(Url::create('http://foo.bar/document/PREFIX-matter-123'));

        self::assertSame(
            'http://foo.bar/document/PREFIX-matter-123',
            $this->strategy->generateRelatedDocumentUrl($document, Mockery::mock(WooDecision::class)),
        );
    }
}
