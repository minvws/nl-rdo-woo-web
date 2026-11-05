<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DocumentId;
use Shared\ValueObject\DocumentNumber;
use Shared\ValueObject\PublicationContext;

use function str_repeat;

final class DocumentNumberTest extends UnitTestCase
{
    public function testFromStringWithLowercase(): void
    {
        $documentNumber = DocumentNumber::fromString('prefix-matter_01-abc.123');

        self::assertSame('prefix-matter_01-abc.123', $documentNumber->toString());
        self::assertSame('prefix-matter_01-abc.123', (string) $documentNumber);
    }

    public function testFromStringWithUppercase(): void
    {
        $this->expectException(InvalidArgumentException::class);

        DocumentNumber::fromString('PREFIX-Matter_01-ABC.123');
    }

    public function testFromUnnormalizedStringWithUppercase(): void
    {
        $documentNumber = DocumentNumber::fromUnnormalizedString('PREFIX-Matter_01-ABC.123');

        self::assertSame('prefix-matter_01-abc.123', $documentNumber->toString());
    }

    public function testItDoesNotApplyA255CharacterLimit(): void
    {
        $value = str_repeat('x', 256);

        self::assertSame($value, DocumentNumber::fromString($value)->toString());
    }

    public function testItBuildsAContextAndIdCandidateWithoutParsing(): void
    {
        $documentNumber = DocumentNumber::fromPublicationContextAndDocumentId(
            PublicationContext::fromString('context'),
            DocumentId::create('doc-01'),
        );

        self::assertSame('context-doc-01', $documentNumber->toString());
    }

    public function testItComparesByValue(): void
    {
        $documentNumber = DocumentNumber::fromString('context-doc-01');

        self::assertTrue($documentNumber->equalTo(DocumentNumber::fromUnnormalizedString('Context-DOC-01')));
        self::assertFalse($documentNumber->equalTo(DocumentNumber::fromString('other-doc-01')));
    }

    #[DataProvider('otherTypeDataProvider')]
    public function testEqualToReturnsFalseForOtherTypes(mixed $other): void
    {
        self::assertFalse(DocumentNumber::fromString('context-doc-01')->equalTo($other));
    }

    /**
     * @return array<string,array{other:mixed}>
     */
    public static function otherTypeDataProvider(): array
    {
        return [
            'null' => ['other' => null],
            'string with same value' => ['other' => 'context-doc-01'],
            'other equatable with same value' => ['other' => DocumentId::create('context-doc-01')],
        ];
    }
}
