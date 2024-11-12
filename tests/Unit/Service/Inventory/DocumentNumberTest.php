<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Inventory;

use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Entity\Document;
use App\Service\Inventory\DocumentMetadata;
use App\Service\Inventory\DocumentNumber;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class DocumentNumberTest extends MockeryTestCase
{
    #[DataProvider('fromDossierAndReferralProvider')]
    public function testFromReferral(string $documentNr, string $prefix, string $documentId, string $referral, string $expected): void
    {
        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('getDocumentPrefix')->andReturn($prefix);

        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('getDocumentNr')->andReturn($documentNr);
        $document->shouldReceive('getDocumentId')->andReturn($documentId);

        $documentNumber = DocumentNumber::fromReferral($dossier, $document, $referral);

        self::assertEquals(
            $expected,
            strval($documentNumber)
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
        ];
    }

    public function testFromDossierAndDocumentMetadata(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('getDocumentPrefix')->andReturn('pr3f1x');

        $documentMetadata = \Mockery::mock(DocumentMetadata::class);
        $documentMetadata->shouldReceive('getMatter')->andReturn('bar');
        $documentMetadata->shouldReceive('getId')->andReturn('foo123');

        $documentNumber = DocumentNumber::fromDossierAndDocumentMetadata($dossier, $documentMetadata);

        self::assertEquals('pr3f1x-bar-foo123', $documentNumber->getValue());
        self::assertEquals('bar', $documentNumber->getMatter());
    }
}
