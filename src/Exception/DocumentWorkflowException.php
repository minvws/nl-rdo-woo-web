<?php

declare(strict_types=1);

namespace App\Exception;

use App\Domain\Publication\Dossier\Type\WooDecision\Entity\Document;

class DocumentWorkflowException extends \RuntimeException
{
    public function __construct(
        string $message,
        private readonly Document $document,
    ) {
        parent::__construct($message);
    }

    public static function forActionNotAllowed(Document $document, string $action): self
    {
        return new self("Action $action not allowed on document", $document);
    }

    public function getDocument(): Document
    {
        return $this->document;
    }
}
