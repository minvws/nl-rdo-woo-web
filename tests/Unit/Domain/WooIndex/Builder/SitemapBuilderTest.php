<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\WooIndex\Builder;

use App\Domain\WooIndex\Builder\DiWooXMLWriter;
use App\Domain\WooIndex\Builder\SitemapBuilder;
use App\Tests\Unit\UnitTestCase;
use Webmozart\Assert\Assert;

final class SitemapBuilderTest extends UnitTestCase
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
        $builder = new SitemapBuilder();
        $builder->setXMLWriterConfigurator(function (DiWooXMLWriter $writer): void {
            $writer->setIndent(true);
        });

        $writer = $builder->open($this->stream);

        $writer->writeElement('test', 'some placeholder content');

        $builder->closeFlush($writer);

        $writer->flush();
        rewind($this->stream);

        $this->assertMatchesTextSnapshot(stream_get_contents($this->stream));
    }
}
