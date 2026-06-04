<?php

declare(strict_types=1);

namespace Shared\Service\Inventory\Sanitizer;

interface InventoryWriterInterface
{
    public function open(string $filename): void;

    public function addHeaders(string ...$headers): void;

    /**
     * @param array<array-key, string>|string ...$cells
     */
    public function addRow(mixed ...$cells): void;

    public function close(): void;

    public function getFileExtension(): string;
}
