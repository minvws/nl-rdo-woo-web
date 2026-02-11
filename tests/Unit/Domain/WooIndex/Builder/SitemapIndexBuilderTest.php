<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\WooIndex\Builder;

use Shared\Domain\WooIndex\Builder\DiWooXMLWriter;
use Shared\Domain\WooIndex\Builder\SitemapIndexBuilder;
use Shared\Tests\Unit\UnitTestCase;
use Webmozart\Assert\Assert;

use function fopen;
use function rewind;
use function stream_get_contents;

final class SitemapIndexBuilderTest extends UnitTestCase
{
    /**
     * @var resource
     */
    private $stream;

    protected function setUp(): void
    {
        parent::setUp();

        $stream = fopen('php://temp', 'wb+');
        Assert::notFalse($stream);
        $this->stream = $stream;
    }

    public function testCreatingSitemapIndex(): void
    {
        $builder = new SitemapIndexBuilder();
        $builder->setXMLWriterConfigurator(function (DiWooXMLWriter $writer): void {
            $writer->setIndent(true);
        });

        $writer = $builder->open($this->stream);

        $builder->addSitemap($writer, 'https://example.com/sitemap1.xml');
        $builder->addSitemap($writer, 'https://example.com/sitemap1.xml');
        $builder->addSitemap($writer, 'https://example.com/sitemap1.xml');
        $builder->closeFlush($writer);

        rewind($this->stream);

        $this->assertMatchesTextSnapshot(stream_get_contents($this->stream));
    }
}
