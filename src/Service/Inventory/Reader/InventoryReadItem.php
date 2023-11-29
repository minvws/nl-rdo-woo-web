<?php

declare(strict_types=1);

namespace App\Service\Inventory\Reader;

use App\Exception\ExcelReaderException;
use App\Service\Inventory\DocumentMetadata;

class InventoryReadItem
{
    public function __construct(
        private readonly ?DocumentMetadata $documentMetadata,
        private readonly int $index,
        private readonly ?ExcelReaderException $exception,
    ) {
    }

    public function getException(): ?ExcelReaderException
    {
        return $this->exception;
    }

    public function getIndex(): int
    {
        return $this->index;
    }

    public function getDocumentMetadata(): ?DocumentMetadata
    {
        return $this->documentMetadata;
    }
}
