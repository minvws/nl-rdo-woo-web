<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shared\Service\ApiPlatformService;

class ApiPlatformServiceTest extends TestCase
{
    /**
     * @param array<string, mixed> $context
     */
    #[DataProvider('contextProvider')]
    public function testConverter(array $context, ?string $expectedResult): void
    {
        self::assertEquals($expectedResult, ApiPlatformService::getCursorFromContext($context));
    }

    /**
     * @return array<string, mixed>
     */
    public static function contextProvider(): array
    {
        return [
            'empty context' => [
                'context' => [],
                'expectedResult' => null,
            ],
            'without filters' => [
                'context' => [
                    'foo' => [],
                ],
                'expectedResult' => null,
            ],
            'with filters but not an array' => [
                'context' => [
                    'filters' => 'foo',
                ],
                'expectedResult' => null,
            ],
            'without pagination' => [
                'context' => [
                    'filters' => [
                        'foo' => [],
                    ],
                ],
                'expectedResult' => null,
            ],
            'with pagination but not an array' => [
                'context' => [
                    'filters' => [
                        'pagination' => 'foo',
                    ],
                ],
                'expectedResult' => null,
            ],
            'without cursor' => [
                'context' => [
                    'filters' => [
                        'pagination' => [
                            'foo' => [],
                        ],
                    ],
                ],
                'expectedResult' => null,
            ],
            'with cursor but not a string' => [
                'context' => [
                    'filters' => [
                        'pagination' => [
                            'cursor' => [],
                        ],
                    ],
                ],
                'expectedResult' => null,
            ],
            'with cursor' => [
                'context' => [
                    'filters' => [
                        'pagination' => [
                            'cursor' => 'foo',
                        ],
                    ],
                ],
                'expectedResult' => 'foo',
            ],
        ];
    }
}
