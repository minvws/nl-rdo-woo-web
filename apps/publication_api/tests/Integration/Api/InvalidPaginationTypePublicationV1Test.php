<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration\Api;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function sprintf;

/**
 * Reproducer for https://github.com/minvws/nl-rdo-woo-web-private/issues/7113.
 */
final class InvalidPaginationTypePublicationV1Test extends ApiPublicationV1TestCase
{
    /**
     * @return iterable<string, array{0: string, 1: string, 2: string}>
     */
    public static function provideInvalidPaginationCombinations(): iterable
    {
        $endpoints = [
            'GET /department' => [Request::METHOD_GET, '/api/publication/v1/department'],
            'GET /organisation' => [Request::METHOD_GET, '/api/publication/v1/organisation'],
        ];

        $invalidQueries = [
            'pagination as array' => 'pagination%5B%5D=x&pagination%5B%5D=y',
            'pagination as integer' => 'pagination=5',
            'pagination as numeric string' => 'pagination=123',
            'pagination as non-numeric string' => 'pagination=hello',
            'pagination as empty string' => 'pagination=',
            'pagination as single-element array' => 'pagination%5B%5D=foo',
            'known param, incorrect type' => 'pagination=foo',
            'known param, incorrect schema (key)' => 'pagination[foo]=bar',
            'known param, incorrect schema (value)' => 'pagination[cursor]=',
        ];

        foreach ($endpoints as $endpointName => $endpoint) {
            foreach ($invalidQueries as $queryName => $queryString) {
                $testName = sprintf('%s with %s', $endpointName, $queryName);
                yield $testName => [$endpoint[0], $endpoint[1], $queryString];
            }
        }
    }

    /**
     * @return iterable<string, array{0: string, 1: string, 2: string}>
     */
    public static function provideValidPaginationCombinations(): iterable
    {
        $endpoints = [
            'GET /department' => [Request::METHOD_GET, '/api/publication/v1/department'],
            'GET /organisation' => [Request::METHOD_GET, '/api/publication/v1/organisation'],
        ];

        $validQueries = [
            'unknown parameter' => 'foo=bar',
            'known parameter, correct schema' => 'pagination[cursor]=foo',
        ];

        foreach ($endpoints as $endpointName => $endpoint) {
            foreach ($validQueries as $queryName => $queryString) {
                $testName = sprintf('%s with %s', $endpointName, $queryName);
                yield $testName => [$endpoint[0], $endpoint[1], $queryString];
            }
        }
    }

    #[DataProvider('provideInvalidPaginationCombinations')]
    public function testInvalidPaginationTypeReturnsUnprocessableEntity(string $method, string $url, string $queryString): void
    {
        $fullUrl = sprintf('%s?%s', $url, $queryString);

        self::createPublicationApiClient()
            ->request($method, $fullUrl);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    #[DataProvider('provideValidPaginationCombinations')]
    public function testValidPaginationTypeReturnsOkRequest(string $method, string $url, string $queryString): void
    {
        $fullUrl = sprintf('%s?%s', $url, $queryString);

        self::createPublicationApiClient()
            ->request($method, $fullUrl);

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
    }
}
