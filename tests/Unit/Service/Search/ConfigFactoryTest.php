<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Search;

use App\Service\Inquiry\InquirySessionService;
use App\Service\Search\ConfigFactory;
use App\Service\Search\Query\Facet\Input\FacetInputFactory;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class ConfigFactoryTest extends MockeryTestCase
{
    public static function queryStringProvider(): array
    {
        return [
            ['', ''],
            ['test', 'test'],
            ['test word', 'test word'],
            ['"test phrase"', '"test phrase"'],
            ['"test -phrase"', '"test -phrase"'],
            ['foo -bar', 'foo +-bar'],
            ['-foo -bar', '+-foo +-bar'],
            ['-foo bar', '+-foo bar'],
            ['foo-bar', 'foo-bar'],
            ['foo - bar', 'foo +- bar'],
        ];
    }

    #[DataProvider('queryStringProvider')]
    public function testQueryString(string $old, string $new): void
    {
        $mock1 = \Mockery::mock(InquirySessionService::class);
        $mock2 = \Mockery::mock(FacetInputFactory::class);
        $factory = new ConfigFactory($mock1, $mock2);

        self::assertEquals($new, $factory->convertQueryStringToNegativeAndValues($old));
    }
}
