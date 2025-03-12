<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\WooIndex\WriterFactory;

use App\Domain\WooIndex\WriterFactory\DiWooXMLWriter;
use App\Tests\Unit\UnitTestCase;

final class DiWooXMLWriterTest extends UnitTestCase
{
    private DiWooXMLWriter $writer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->writer = new DiWooXMLWriter();
        $this->writer->openMemory();
        $this->writer->setIndent(true);
    }

    public function testStartDiWooElement(): void
    {
        $this->writer->startDiWooElement('example');
        $this->writer->endElement();

        $this->assertMatchesTextSnapshot($this->writer->flush());
    }

    public function testDiWooElement(): void
    {
        $this->writer->writeDiWooElement('example', 'my-content');

        $this->assertMatchesTextSnapshot($this->writer->flush());
    }
}
