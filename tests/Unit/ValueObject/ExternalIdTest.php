<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DocumentId;
use Shared\ValueObject\ExternalId;

class ExternalIdTest extends UnitTestCase
{
    public function testCreate(): void
    {
        $id = $this->getFaker()->uuid();

        $externalId = ExternalId::create($id);
        $this->assertEquals($id, $externalId->__toString());
    }

    public function testEqualToReturnsTrueForSameValue(): void
    {
        $this->assertTrue(ExternalId::create('abc-123')->equalTo(ExternalId::create('abc-123')));
    }

    public function testEqualToReturnsFalseForDifferentValue(): void
    {
        $this->assertFalse(ExternalId::create('abc-123')->equalTo(ExternalId::create('def-456')));
    }

    #[DataProvider('otherTypeDataProvider')]
    public function testEqualToReturnsFalseForOtherTypes(mixed $other): void
    {
        $this->assertFalse(ExternalId::create('abc-123')->equalTo($other));
    }

    /**
     * @return array<string, array{other: mixed}>
     */
    public static function otherTypeDataProvider(): array
    {
        return [
            'null' => ['other' => null],
            'string with same value' => ['other' => 'abc-123'],
            'other equatable with same value' => ['other' => DocumentId::create('abc-123')],
        ];
    }

    #[DataProvider('invalidExternalIdDataProvider')]
    public function testCreateWithInvalidString(string $id): void
    {
        $this->expectException(InvalidArgumentException::class);
        ExternalId::create($id);
    }

    /**
     * @return array<string, list<string>>
     */
    public static function invalidExternalIdDataProvider(): array
    {
        return [
            'invalid characters' => ['<>'],
            'string too short (0 chars)' => [''],
            'string too long (> 128 chars)' => [
                '123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789',
            ],
        ];
    }
}
