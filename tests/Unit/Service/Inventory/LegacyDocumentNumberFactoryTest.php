<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Inventory;

use InvalidArgumentException;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Service\Inventory\LegacyDocumentNumberFactory;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DocumentId;
use Shared\ValueObject\DocumentMatter;
use Shared\ValueObject\DocumentNumber as CanonicalDocumentNumber;

use function str_repeat;

final class LegacyDocumentNumberFactoryTest extends UnitTestCase
{
    public function testTheFactoryPreservesLegacyParsingButReturnsTheCanonicalType(): void
    {
        $factory = new LegacyDocumentNumberFactory();

        $result = $factory->fromPrefixMatterAndInput(
            'prefix',
            DocumentMatter::create('matter'),
            'prefix-matter-doc-01',
        );

        self::assertInstanceOf(CanonicalDocumentNumber::class, $result);
        self::assertSame('prefix-matter-doc-01', $result->toString());
    }

    public function testTheFactoryDoesNotApplyTheLegacy255CharacterGuard(): void
    {
        $longPrefix = str_repeat('p', 253);

        self::assertSame(
            $longPrefix . '-doc-01',
            new LegacyDocumentNumberFactory()->fromPrefixMatterAndInput($longPrefix, null, 'doc-01')->toString(),
        );
    }

    public function testTheFactoryValidatesTheDocumentIdMaximumLength(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(DocumentId::ERROR_INVALID_LENGTH);

        new LegacyDocumentNumberFactory()->fromPrefixMatterAndInput(
            'prefix',
            null,
            str_repeat('x', DocumentId::MAX_LENGTH + 1),
        );
    }

    #[DataProvider('fromDossierAndReferralProvider')]
    public function testFromReferral(
        string $documentNumber,
        string $prefix,
        string $documentId,
        string $referral,
        string $expected,
    ): void {
        $dossier = Mockery::mock(WooDecision::class);
        $dossier->expects('getDocumentPrefix')->times(1)->andReturn($prefix);

        $document = Mockery::mock(Document::class);
        $document->expects('getDocumentNumber')
            ->andReturn(CanonicalDocumentNumber::fromString($documentNumber));
        $document->expects('getDocumentId')->times(1)->andReturn(DocumentId::create($documentId));

        $result = new LegacyDocumentNumberFactory()->fromReferral($dossier, $document, $referral);

        self::assertSame($expected, $result->toString());
    }

    /**
     * @return array<string, array{
     *     documentNumber: string,
     *     prefix: string,
     *     documentId: string,
     *     referral: string,
     *     expected: string
     * }>
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

    public function testTheFactoryFromDossierAndDocumentReturnsTheCanonicalType(): void
    {
        $dossier = new WooDecision();
        $dossier->setDocumentPrefix('prefix');

        $document = new Document();
        $document->setDocumentNumber(CanonicalDocumentNumber::fromString('prefix-matter-doc-01'));
        $document->setDocumentId(DocumentId::create('doc-01'));

        $result = new LegacyDocumentNumberFactory()->fromDossierAndDocument(
            $dossier,
            $document,
        );

        self::assertInstanceOf(CanonicalDocumentNumber::class, $result);
        self::assertSame('prefix-matter-doc-01', $result->toString());
    }
}
