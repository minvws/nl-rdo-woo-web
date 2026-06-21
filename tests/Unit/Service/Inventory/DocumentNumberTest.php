<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Inventory;

use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Service\Inventory\DocumentMetadata;
use Shared\Service\Inventory\DocumentNumber;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DocumentId;
use Shared\ValueObject\DocumentMatter;

use function strval;

class DocumentNumberTest extends UnitTestCase
{
    #[DataProvider('fromDossierAndReferralProvider')]
    public function testFromReferral(string $documentNr, string $prefix, string $documentId, string $referral, string $expected): void
    {
        $dossier = Mockery::mock(WooDecision::class);
        $dossier->expects('getDocumentPrefix')->times(3)->andReturn($prefix);

        $document = Mockery::mock(Document::class);
        $document->expects('getDocumentNr')->andReturn($documentNr);
        $document->expects('getDocumentId')->times(3)->andReturn(DocumentId::create($documentId));

        $documentNumber = DocumentNumber::fromReferral($dossier, $document, $referral);

        self::assertEquals(
            $expected,
            strval($documentNumber),
        );
    }

    /**
     * @return array<string, array{prefix:string, referral:string, expected:string}>
     */
    public static function fromDossierAndReferralProvider(): array
    {
        return [
            'separated-by-dash' => [
                'documentNr' => 'pr3f1x-docmatter-123',
                'prefix' => 'pr3f1x',
                'documentId' => '123',
                'referral' => 'm4tt3r-d0c1d.suffix',
                'expected' => 'pr3f1x-m4tt3r-d0c1d.suffix',
            ],
            'separated-by-underscore' => [
                'documentNr' => 'pr3f1x-docmatter-123',
                'prefix' => 'pr3f1x',
                'documentId' => '123',
                'referral' => 'm4tt3r_d0c1d.suffix',
                'expected' => 'pr3f1x-m4tt3r-d0c1d.suffix',
            ],
            'document-id-only' => [
                'documentNr' => 'pr3f1x-docmatter-123',
                'prefix' => 'pr3f1x',
                'documentId' => '123',
                'referral' => 'd0c1d',
                'expected' => 'pr3f1x-docmatter-d0c1d',
            ],
            'with-prefix-included' => [
                'documentNr' => 'pr3f1x-docmatter-123',
                'prefix' => 'pr3f1x',
                'documentId' => '123',
                'referral' => 'pr3f1x-m4tt3r-d0c1d.suffix',
                'expected' => 'pr3f1x-m4tt3r-d0c1d.suffix',
            ],
            'document-id-only-matter-with-dash' => [
                'documentNr' => 'pr3f1x-doc-matter-123',
                'prefix' => 'pr3f1x',
                'documentId' => '123',
                'referral' => 'd0c1d',
                'expected' => 'pr3f1x-doc-matter-d0c1d',
            ],
            'other-matter-with-dash' => [
                'documentNr' => 'pr3f1x-doc-matter-123',
                'prefix' => 'pr3f1x',
                'documentId' => '123',
                'referral' => 'other-doc-matter-d0c1d',
                'expected' => 'pr3f1x-other-doc-matter-d0c1d',
            ],
            'without-matter' => [
                'documentNr' => 'pr3f1x-123',
                'prefix' => 'pr3f1x',
                'documentId' => '123',
                'referral' => 'other-d0c1d',
                'expected' => 'pr3f1x-other-d0c1d',
            ],
        ];
    }

    public function testFromDossierAndDocumentMetadata(): void
    {
        $dossier = Mockery::mock(WooDecision::class);
        $dossier->expects('getDocumentPrefix')->andReturn('pr3f1x');

        $matter = DocumentMatter::create('bar');
        $documentMetadata = Mockery::mock(DocumentMetadata::class);
        $documentMetadata->expects('getMatter')->andReturn($matter);
        $documentMetadata->expects('getId')->andReturn(DocumentId::create('foo123'));

        $documentNumber = DocumentNumber::fromDossierAndDocumentMetadata($dossier, $documentMetadata);

        self::assertEquals('pr3f1x-bar-foo123', $documentNumber->getValue());
        self::assertEquals($matter, $documentNumber->getMatter());
    }
}
