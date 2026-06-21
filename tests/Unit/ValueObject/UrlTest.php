<?php

declare(strict_types=1);

namespace Unit\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\Url;

class UrlTest extends UnitTestCase
{
    #[DataProvider('validUrlDataProvider')]
    public function testCreate(string $url): void
    {
        $this->assertEquals($url, Url::create($url)->toString());
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function validUrlDataProvider(): array
    {
        return [
            'http' => ['http://www.foo.com'],
            'http without www' => ['http://foo.com'],
            'http without domain' => ['http://foo'],
            'ftp' => ['ftp://foo'],
        ];
    }

    #[DataProvider('invalidUrlDataProvider')]
    public function testCreateWithInvalidUrl(string $url): void
    {
        $this->expectException(InvalidArgumentException::class);
        Url::create($url);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function invalidUrlDataProvider(): array
    {
        return [
            'missing protocol' => ['foo'],
            'missing /' => ['http:/foo'],
            'missing :' => ['http//foo'],
        ];
    }
}
