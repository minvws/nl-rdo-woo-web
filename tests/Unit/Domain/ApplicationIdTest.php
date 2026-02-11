<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use Shared\ApplicationId;
use Shared\Tests\Unit\UnitTestCase;
use ValueError;

final class ApplicationIdTest extends UnitTestCase
{
    public function testApplicationId(): void
    {
        $this->assertMatchesObjectSnapshot(ApplicationId::cases());
    }

    #[DataProvider('getFromStringData')]
    public function testFromStringWithValidValues(?string $input, ApplicationId $expected): void
    {
        $this->assertSame($expected, ApplicationId::fromString($input));
    }

    public function testFromStringWithInvalidValuesThrowsException(): void
    {
        $this->expectException(ValueError::class);

        ApplicationId::fromString('ACME');
    }

    /**
     * @return array<string,array{input:?string,expected:ApplicationId}>
     */
    public static function getFromStringData(): array
    {
        return [
            'all lower case' => [
                'input' => 'admin',
                'expected' => ApplicationId::ADMIN,
            ],
            'all upper case' => [
                'input' => 'PUBLIC',
                'expected' => ApplicationId::PUBLIC,
            ],
            'mixed cases' => [
                'input' => 'PUBlication_API',
                'expected' => ApplicationId::PUBLICATION_API,
            ],
            'null uses fallback' => [
                'input' => null,
                'expected' => ApplicationId::SHARED,
            ],
            'empty string uses fallback' => [
                'input' => '',
                'expected' => ApplicationId::SHARED,
            ],
        ];
    }
}
