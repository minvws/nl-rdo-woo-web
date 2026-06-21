<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DocumentMatter;

use function str_repeat;

class DocumentMatterTest extends UnitTestCase
{
    public function testCreateWithSimpleValue(): void
    {
        $documentMatter = DocumentMatter::create('abc-123');

        $this->assertEquals('abc-123', $documentMatter->toString());
    }

    public function testCreateAtMaxLengthBoundary(): void
    {
        $value = str_repeat('a', 255);

        $documentMatter = DocumentMatter::create($value);

        $this->assertEquals($value, $documentMatter->toString());
    }

    #[DataProvider('invalidDocumentMatterDataProvider')]
    public function testCreateWithInvalidValue(string $value, int $expectedErrorCode): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode($expectedErrorCode);

        DocumentMatter::create($value);
    }

    /**
     * @return array<string, array{value: string, expectedErrorCode: int}>
     */
    public static function invalidDocumentMatterDataProvider(): array
    {
        return [
            'empty string' => [
                'value' => '',
                'expectedErrorCode' => DocumentMatter::ERROR_EMPTY,
            ],
            'forward slash' => [
                'value' => 'foo/bar',
                'expectedErrorCode' => DocumentMatter::ERROR_INVALID_CHARACTERS,
            ],
            'whitespace' => [
                'value' => 'foo bar',
                'expectedErrorCode' => DocumentMatter::ERROR_INVALID_CHARACTERS,
            ],
            'too long (> 255 chars)' => [
                'value' => str_repeat('a', 256),
                'expectedErrorCode' => DocumentMatter::ERROR_INVALID_LENGTH,
            ],
        ];
    }
}
