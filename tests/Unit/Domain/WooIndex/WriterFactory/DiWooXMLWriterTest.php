<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\WooIndex\WriterFactory;

use Shared\Domain\WooIndex\Builder\DiWooXMLWriter;
use Shared\Tests\Unit\UnitTestCase;
use Webmozart\Assert\Assert;

use function fopen;
use function rewind;
use function stream_get_contents;

final class DiWooXMLWriterTest extends UnitTestCase
{
    /**
     * @var resource
     */
    protected $stream;

    private DiWooXMLWriter $writer;

    protected function setUp(): void
    {
        parent::setUp();

        $stream = fopen('php://temp', 'wb+');
        Assert::notFalse($stream);
        $this->stream = $stream;

        $this->writer = DiWooXMLWriter::toStream($this->stream);
        $this->writer->setIndent(true);
    }

    public function testStartDiWooElement(): void
    {
        $this->writer->startDiWooElement('example');
        $this->writer->endElement();

        $this->writer->flush();
        rewind($this->stream);

        $this->assertMatchesTextSnapshot(stream_get_contents($this->stream));
    }

    public function testDiWooElement(): void
    {
        $this->writer->writeDiWooElement('example', 'my-content');

        $this->writer->flush();
        rewind($this->stream);

        $this->assertMatchesTextSnapshot(stream_get_contents($this->stream));
    }
}
