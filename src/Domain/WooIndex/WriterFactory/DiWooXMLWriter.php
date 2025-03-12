<?php

declare(strict_types=1);

namespace App\Domain\WooIndex\WriterFactory;

final class DiWooXMLWriter extends \XMLWriter
{
    private const DIWOO_NS = 'diwoo';

    public function startDiWooElement(string $name): void
    {
        $this->startElementNs(prefix: self::DIWOO_NS, name: $name, namespace: null);
    }

    public function writeDiWooElement(string $name, ?string $content): void
    {
        $this->writeElementNs(prefix: self::DIWOO_NS, name: $name, namespace: null, content: $content);
    }
}
