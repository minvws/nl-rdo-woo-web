<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DocumentId;

use function str_repeat;

class DocumentIdTest extends UnitTestCase
{
    public function testCreateReturnsDocumentId(): void
    {
        $documentId = DocumentId::create('abc123');

        $this->assertSame('abc123', $documentId->__toString());
    }

    public function testCreateAcceptsDotsInId(): void
    {
        $documentId = DocumentId::create('abc.123');

        $this->assertSame('abc.123', $documentId->__toString());
    }

    public function testCreateAcceptsUpperCasedLettersInId(): void
    {
        $documentId = DocumentId::create('ABC.123');

        $this->assertSame('abc.123', $documentId->__toString());
    }

    public function testCreateAcceptsDashesInId(): void
    {
        $documentId = DocumentId::create('Abc-123');

        $this->assertSame('abc-123', $documentId->__toString());
    }

    public function testCreateAcceptsMinimumLengthId(): void
    {
        $documentId = DocumentId::create(str_repeat('a', DocumentId::MIN_LENGTH));

        $this->assertInstanceOf(DocumentId::class, $documentId);
    }

    public function testCreateAcceptsMaximumLengthId(): void
    {
        $id = str_repeat('a', DocumentId::MAX_LENGTH);

        $documentId = DocumentId::create($id);

        $this->assertSame($id, $documentId->__toString());
    }

    #[DataProvider('invalidDocumentIdDataProvider')]
    public function testCreateThrowsOnInvalidId(string $id, int $expectedCode): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode($expectedCode);

        DocumentId::create($id);
    }

    public function testEqualToReturnsTrueForSameId(): void
    {
        $documentId = DocumentId::create('abc-123');

        $this->assertTrue($documentId->equalTo(DocumentId::create('abc-123')));
    }

    public function testEqualToIgnoresCase(): void
    {
        $documentId = DocumentId::create('ABC-123');

        $this->assertTrue($documentId->equalTo(DocumentId::create('abc-123')));
    }

    public function testEqualToReturnsFalseForDifferentId(): void
    {
        $documentId = DocumentId::create('abc-123');

        $this->assertFalse($documentId->equalTo(DocumentId::create('def-456')));
    }

    /**
     * @return array<string,array{id:string,expectedCode:int}>
     */
    public static function invalidDocumentIdDataProvider(): array
    {
        return [
            'empty string' => [
                'id' => '',
                'expectedCode' => DocumentId::ERROR_EMPTY,
            ],
            'string too long (171 chars)' => [
                'id' => str_repeat('a', DocumentId::MAX_LENGTH + 1),
                'expectedCode' => DocumentId::ERROR_INVALID_LENGTH,
            ],
            'spaces' => [
                'id' => 'abc 123',
                'expectedCode' => DocumentId::ERROR_INVALID_FORMAT,
            ],
            'special characters' => [
                'id' => 'abc!123',
                'expectedCode' => DocumentId::ERROR_INVALID_FORMAT,
            ],
            'slash' => [
                'id' => 'abc/123',
                'expectedCode' => DocumentId::ERROR_INVALID_FORMAT,
            ],
            'underscore' => [
                'id' => 'abc_123',
                'expectedCode' => DocumentId::ERROR_INVALID_FORMAT,
            ],
        ];
    }
}
