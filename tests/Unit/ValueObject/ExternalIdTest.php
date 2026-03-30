<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\ExternalId;

class ExternalIdTest extends UnitTestCase
{
    public function testCreate(): void
    {
        $id = $this->getFaker()->uuid();

        $externalId = ExternalId::create($id);
        $this->assertEquals($id, $externalId->__toString());
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
