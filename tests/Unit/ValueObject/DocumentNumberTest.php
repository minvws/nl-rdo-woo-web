<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\ValueObject;

use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DocumentId;
use Shared\ValueObject\DocumentNumber;
use Shared\ValueObject\PublicationContext;

use function str_repeat;

final class DocumentNumberTest extends UnitTestCase
{
    public function testItPreservesTheExactInput(): void
    {
        $documentNumber = DocumentNumber::fromString('PREFIX-Matter_01-ABC.123');

        self::assertSame('PREFIX-Matter_01-ABC.123', $documentNumber->toString());
        self::assertSame('PREFIX-Matter_01-ABC.123', (string) $documentNumber);
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

    public function testItComparesByExactValue(): void
    {
        $same = DocumentNumber::fromString('Context-DOC-01');

        self::assertTrue($same->equalTo(DocumentNumber::fromString('Context-DOC-01')));
        self::assertFalse($same->equalTo(DocumentNumber::fromString('context-doc-01')));
    }
}
