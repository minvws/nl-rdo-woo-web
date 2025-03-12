<?php

declare(strict_types=1);

namespace App\Domain\WooIndex\WriterFactory;

use App\Domain\WooIndex\DiWooException;

final class FailedCreatingXmlException extends \LogicException implements DiWooException
{
    /**
     * @param class-string<WriterFactory> $writerFactoryClass
     */
    public static function create(string $path, string $writerFactoryClass): self
    {
        return new self(sprintf('Could not create XML file at: "%s" using "%s"', $path, $writerFactoryClass));
    }
}
