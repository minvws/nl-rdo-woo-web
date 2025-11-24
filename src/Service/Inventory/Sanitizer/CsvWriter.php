<?php

declare(strict_types=1);

namespace Shared\Service\Inventory\Sanitizer;

use Shared\Exception\InventorySanitizerException;

class CsvWriter implements InventoryWriterInterface
{
    /**
     * @var false|resource|closed-resource
     */
    private $filePointer = false;

    public function open(string $filename): void
    {
        $this->filePointer = fopen($filename, 'w');
        if (! $this->filePointer) {
            throw new InventorySanitizerException('Could not open temporary file for sanitized inventory.');
        }
    }

    public function addHeaders(string ...$headers): void
    {
        // No difference with a 'normal' row so forward the call
        $this->addRow(...$headers);
    }

    /**
     * @param array<string>|string ...$cells
     */
    public function addRow(mixed ...$cells): void
    {
        if (! $this->filePointer) {
            throw new InventorySanitizerException('Cannot write to file');
        }

        $values = [];
        foreach ($cells as $value) {
            if (is_array($value)) {
                $values[] = implode(' ', $value);
            } else {
                $values[] = $value;
            }
        }

        fputcsv($this->filePointer, $values);
    }

    public function close(): void
    {
        if (! $this->filePointer) {
            throw new InventorySanitizerException('Cannot close file');
        }

        fclose($this->filePointer);
    }

    public function getFileExtension(): string
    {
        return 'csv';
    }
}
