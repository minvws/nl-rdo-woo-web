<?php

declare(strict_types=1);

namespace Shared\Domain\WooIndex\Builder;

use Webmozart\Assert\Assert;
use XMLWriter;

use function fwrite;
use function strlen;

final class DiWooXMLWriter
{
    private const string DIWOO_NS = 'diwoo';

    /**
     * @param resource $stream
     */
    private function __construct(private $stream, private XMLWriter $writer = new XMLWriter())
    {
    }

    /**
     * @param resource $stream
     */
    public static function toStream($stream): static
    {
        Assert::resource($stream);

        $self = new self($stream);

        Assert::notFalse($self->writer->openMemory());

        return $self;
    }

    public function startDocument(?string $version = '1.0', ?string $encoding = null, ?string $standalone = null): bool
    {
        return $this->writer->startDocument($version, $encoding, $standalone);
    }

    public function endDocument(): bool
    {
        return $this->writer->endDocument();
    }

    public function startElement(string $name): bool
    {
        return $this->writer->startElement($name);
    }

    public function endElement(): bool
    {
        return $this->writer->endElement();
    }

    public function startElementNs(?string $prefix, string $name, ?string $namespace): bool
    {
        return $this->writer->startElementNs($prefix, $name, $namespace);
    }

    public function writeElementNs(?string $prefix, string $name, ?string $namespace, ?string $content = null): bool
    {
        return $this->writer->writeElementNs($prefix, $name, $namespace, $content);
    }

    public function writeElement(string $name, ?string $content = null): bool
    {
        return $this->writer->writeElement($name, $content);
    }

    public function writeAttribute(string $name, string $value): bool
    {
        return $this->writer->writeAttribute($name, $value);
    }

    public function text(string $content): bool
    {
        return $this->writer->text($content);
    }

    public function startDiWooElement(string $name): void
    {
        $this->startElementNs(prefix: self::DIWOO_NS, name: $name, namespace: null);
    }

    public function writeDiWooElement(string $name, ?string $content): void
    {
        $this->writeElementNs(prefix: self::DIWOO_NS, name: $name, namespace: null, content: $content);
    }

    public function setIndent(bool $enable): bool
    {
        return $this->writer->setIndent($enable);
    }

    public function flush(): int
    {
        $contents = $this->writer->outputMemory();
        $size = strlen($contents);

        fwrite($this->stream, $contents);

        return $size;
    }
}
