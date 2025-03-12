<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\WooIndex\WriterFactory;

use App\Domain\WooIndex\WriterFactory\DiWooXMLWriter;
use App\Domain\WooIndex\WriterFactory\FailedCreatingXmlException;
use App\Domain\WooIndex\WriterFactory\FileWriterFactory;
use App\Domain\WooIndex\WriterFactory\MemoryWriterFactory;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use org\bovigo\vfs\vfsStream;

final class WriterFactoryTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        vfsStream::setup();
    }

    public function testCreateFileWriter(): void
    {
        $path = 'vfs://root/example.xml';
        $writer = (new FileWriterFactory())->create($path);
        $this->writeExampleDocument($writer);

        $this->assertMatchesFileSnapshot($path);
    }

    public function testCreateFileWriterThrowsException(): void
    {
        $writer = \Mockery::mock(DiWooXMLWriter::class);
        $writer->shouldReceive('openUri')->once()->andReturnFalse();

        /** @var FileWriterFactory&MockInterface $factory */
        $factory = \Mockery::mock(FileWriterFactory::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $factory
            ->shouldReceive('newXmlWriter')
            ->once()
            ->andReturn($writer);

        $path = 'vfs://root/not_relevant.xml';

        $this->expectExceptionObject(FailedCreatingXmlException::create($path, FileWriterFactory::class));

        $factory->create($path);
    }

    public function testCreateMemoryWriter(): void
    {
        $path = 'vfs://root/not_relevant.xml';
        $writer = (new MemoryWriterFactory())->create($path);
        $buffer = $this->writeExampleDocument($writer);

        $this->assertIsNotInt($buffer);
        $this->assertFileDoesNotExist($path);
        $this->assertMatchesXmlSnapshot($buffer);
    }

    public function testCreateMemoryWriterThrowsException(): void
    {
        $writer = \Mockery::mock(DiWooXMLWriter::class);
        $writer->shouldReceive('openMemory')->once()->andReturnFalse();

        /** @var MemoryWriterFactory&MockInterface $factory */
        $factory = \Mockery::mock(MemoryWriterFactory::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $factory
            ->shouldReceive('newXmlWriter')
            ->once()
            ->andReturn($writer);

        $path = 'vfs://root/not_relevant.xml';

        $this->expectExceptionObject(FailedCreatingXmlException::create('in-memory', MemoryWriterFactory::class));

        $factory->create($path);
    }

    private function writeExampleDocument(\XMLWriter $writer): int|string
    {
        $writer->setIndent(true);

        $writer->startDocument('1.0', 'UTF-8');
        $writer->startElement('root');
        $writer->writeElement('foo', 'bar');
        $writer->endElement();
        $writer->endDocument();

        return $writer->flush();
    }
}
