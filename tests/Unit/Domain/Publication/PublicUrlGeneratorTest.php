<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication;

use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Shared\Domain\Publication\PublicUrlGenerator;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Routing\RouterInterface;

final class PublicUrlGeneratorTest extends UnitTestCase
{
    #[DataProvider('buildUrlFromPathProvider')]
    public function testBuildUrlFromPath(string $baseUrl, string $path, string $expectedUrl): void
    {
        $router = Mockery::mock(RouterInterface::class);
        $generator = new PublicUrlGenerator($baseUrl, $router);

        self::assertSame($expectedUrl, $generator->buildUrlFromPath($path));
    }

    /**
     * @return array<string, array{baseUrl: string, path: string, expectedUrl: string}>
     */
    public static function buildUrlFromPathProvider(): array
    {
        return [
            'path with leading slash' => [
                'baseUrl' => 'https://foo.com',
                'path' => '/some/path',
                'expectedUrl' => 'https://foo.com/some/path',
            ],
            'path without leading slash' => [
                'baseUrl' => 'https://foo.com',
                'path' => 'some/path',
                'expectedUrl' => 'https://foo.com/some/path',
            ],
            'empty path' => [
                'baseUrl' => 'https://bar.com',
                'path' => '',
                'expectedUrl' => 'https://bar.com',
            ],
            'path with query string' => [
                'baseUrl' => 'https://example.com',
                'path' => '/search?foo=bar',
                'expectedUrl' => 'https://example.com/search?foo=bar',
            ],
        ];
    }

    public function testBuildUrlFromRouteReturnsUrlWithGeneratedPath(): void
    {
        $router = Mockery::mock(RouterInterface::class);
        $router->expects('generate')
            ->with('my_route', ['id' => 42])
            ->andReturn('/generated/path');

        $generator = new PublicUrlGenerator('https://example.com', $router);

        $result = $generator->buildUrlFromRoute('my_route', ['id' => 42]);

        self::assertSame('https://example.com/generated/path', $result->toString());
    }
}
