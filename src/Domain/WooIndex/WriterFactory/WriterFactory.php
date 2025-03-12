<?php

declare(strict_types=1);

namespace App\Domain\WooIndex\WriterFactory;

interface WriterFactory
{
    public function create(string $path): DiWooXMLWriter;
}
