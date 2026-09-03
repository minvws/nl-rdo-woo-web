<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Inventory;

use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Service\Inventory\DocumentNumber;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DocumentId;

use function strval;

class DocumentNumberTest extends UnitTestCase
{
    #[DataProvider('fromDossierAndReferralProvider')]
    public function testFromReferral(string $documentNumber, string $prefix, string $documentId, string $referral, string $expected): void
    {
        $dossier = Mockery::mock(WooDecision::class);
        $dossier->expects('getDocumentPrefix')->times(3)->andReturn($prefix);

        $document = Mockery::mock(Document::class);
        $document->expects('getdocumentNumber')->andReturn($documentNumber);
        $document->expects('getDocumentId')->times(2)->andReturn(DocumentId::create($documentId));

        self::assertEquals($expected, strval(DocumentNumber::fromReferral($dossier, $document, $referral)));
    }

    /**
     * @return array<string, array{prefix:string, referral:string, expected:string}>
     */
    public static function fromDossierAndReferralProvider(): array
    {
        return [
            'separated-by-dash' => [
                'documentNumber' => 'pr3f1x-docmatter-123',
                'prefix' => 'pr3f1x',
                'documentId' => '123',
                'referral' => 'm4tt3r-d0c1d.suffix',
                'expected' => 'pr3f1x-m4tt3r-d0c1d.suffix',
            ],
            'separated-by-underscore' => [
                'documentNumber' => 'pr3f1x-docmatter-123',
                'prefix' => 'pr3f1x',
                'documentId' => '123',
                'referral' => 'm4tt3r_d0c1d.suffix',
                'expected' => 'pr3f1x-m4tt3r-d0c1d.suffix',
            ],
            'document-id-only' => [
                'documentNumber' => 'pr3f1x-docmatter-123',
                'prefix' => 'pr3f1x',
                'documentId' => '123',
                'referral' => 'd0c1d',
                'expected' => 'pr3f1x-docmatter-d0c1d',
            ],
            'with-prefix-included' => [
                'documentNumber' => 'pr3f1x-docmatter-123',
                'prefix' => 'pr3f1x',
                'documentId' => '123',
                'referral' => 'pr3f1x-m4tt3r-d0c1d.suffix',
                'expected' => 'pr3f1x-m4tt3r-d0c1d.suffix',
            ],
            'document-id-only-matter-with-dash' => [
                'documentNumber' => 'pr3f1x-doc-matter-123',
                'prefix' => 'pr3f1x',
                'documentId' => '123',
                'referral' => 'd0c1d',
                'expected' => 'pr3f1x-doc-matter-d0c1d',
            ],
            'other-matter-with-dash' => [
                'documentNumber' => 'pr3f1x-doc-matter-123',
                'prefix' => 'pr3f1x',
                'documentId' => '123',
                'referral' => 'other-doc-matter-d0c1d',
                'expected' => 'pr3f1x-other-doc-matter-d0c1d',
            ],
            'without-matter' => [
                'documentNumber' => 'pr3f1x-123',
                'prefix' => 'pr3f1x',
                'documentId' => '123',
                'referral' => 'other-d0c1d',
                'expected' => 'pr3f1x-other-d0c1d',
            ],
        ];
    }
}
