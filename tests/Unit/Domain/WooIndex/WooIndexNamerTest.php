<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\WooIndex;

use App\Domain\WooIndex\WooIndexNamer;
use App\Tests\Unit\UnitTestCase;
use Carbon\CarbonImmutable;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;

final class WooIndexNamerTest extends UnitTestCase
{
    private CarbonImmutable $now;

    private WooIndexNamer&MockInterface $wooIndexNamer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->now = CarbonImmutable::parse('2024-04-06 13:37:42.123456');

        $this->wooIndexNamer = \Mockery::mock(WooIndexNamer::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
    }

    public function testGetWooIndexRunId(): void
    {
        $this->setTestNow($this->now);

        $this->wooIndexNamer->shouldReceive('getRandomRunIdSuffix')->andReturn('random_string');

        $result = $this->wooIndexNamer->getWooIndexRunId();

        $this->assertSame('20240406_010442_123456__random_string', $result);
    }

    public function testGetWooIndexRunIdAreUnique(): void
    {
        $this->setTestNow($this->now);

        $resultOne = $this->wooIndexNamer->getWooIndexRunId();
        $resultTwo = $this->wooIndexNamer->getWooIndexRunId();

        $this->assertNotSame($resultOne, $resultTwo);
    }

    public function testGetWooIndexRunIdWithPathSuffixProvided(): void
    {
        $this->setTestNow($this->now);

        $result = $this->wooIndexNamer->getWooIndexRunId('my_path_suffix');

        $this->assertSame('20240406_010442_123456__my_path_suffix', $result);
    }

    #[DataProvider('getSitemapNameData')]
    public function testGetSitemapName(int $input, string $expected): void
    {
        $this->assertSame($expected, $this->wooIndexNamer->getSitemapName($input));
    }

    /**
     * @return array<string,array{input:int,expected:string}>
     */
    public static function getSitemapNameData(): array
    {
        return [
            'single digit' => [
                'input' => 1,
                'expected' => 'sitemap-00001.xml',
            ],
            'double digit' => [
                'input' => 33,
                'expected' => 'sitemap-00033.xml',
            ],
            'quadrupple digit' => [
                'input' => 1337,
                'expected' => 'sitemap-01337.xml',
            ],
            'quintuple digit' => [
                'input' => 12_456,
                'expected' => 'sitemap-12456.xml',
            ],
            'sextuple digit' => [
                'input' => 169_069,
                'expected' => 'sitemap-169069.xml',
            ],
        ];
    }

    public function getSitemapIndexName(): void
    {
        $this->assertSame('sitemap-index.xml', $this->wooIndexNamer->getSitemapIndexName());
    }

    /**
     * @param list<string> $input
     */
    #[DataProvider('getJoinPathsData')]
    public function testJoinPaths(array $input, string $expected): void
    {
        $this->assertsame($expected, $this->wooIndexNamer->joinPaths(...$input));
    }

    /**
     * @return array<string,array{input:list<string|int|null>,expected:string}>
     */
    public static function getJoinPathsData(): array
    {
        return [
            'single element' => [
                'input' => ['my///path'],
                'expected' => 'my/path',
            ],
            'no elements' => [
                'input' => [],
                'expected' => '',
            ],
            'multiple elements' => [
                'input' => ['my/', 42, 'path', '/one/', '/more'],
                'expected' => 'my/42/path/one/more',
            ],
            'multiple elements starting and ending with /' => [
                'input' => ['/my/', 'path', '/one/', '/more/'],
                'expected' => '/my/path/one/more/',
            ],
            'multiple elements with empty path elements' => [
                'input' => ['', 'this/', '/is', 'my', null, 'void'],
                'expected' => 'this/is/my/void',
            ],
            'null values only' => [
                'input' => [null, null],
                'expected' => '',
            ],
            'combine all types' => [
                'input' => ['', 'this/', '/is', '////', 42, null, 'null', 'path'],
                'expected' => 'this/is/42/null/path',
            ],
            'path with the vfs protocol' => [
                'input' => ['vfs://root', 'my', 'path'],
                'expected' => 'vfs://root/my/path',
            ],
            'path with the memory protocol' => [
                'input' => ['memory://foobar', 'my', 'path'],
                'expected' => 'memory://foobar/my/path',
            ],
        ];
    }
}
