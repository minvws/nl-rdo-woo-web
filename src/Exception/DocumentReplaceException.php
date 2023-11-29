<?php

declare(strict_types=1);

namespace App\Exception;

use App\Entity\Document;

class DocumentReplaceException extends \RuntimeException
{
    public function __construct(
        string $message,
        private readonly Document $document,
        private readonly string $filename,
    ) {
        parent::__construct($message);
    }

    public function getDocument(): Document
    {
        return $this->document;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public static function forFilenameMismatch(Document $document, string $filename): self
    {
        return new self("Filename doesn't match document number", $document, $filename);
    }
}
