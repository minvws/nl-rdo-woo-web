<?php

declare(strict_types=1);

namespace App\Domain\Publication\MainDocument;

use App\Domain\Publication\MainDocument\AbstractMainDocument as TDocument;

/**
 * @template TDocument of AbstractMainDocument
 *
 * @property ?TDocument $document
 */
trait HasMainDocument
{
    /**
     * @phpstan-return ?TDocument
     */
    public function getDocument(): ?AbstractMainDocument
    {
        /** @var ?TDocument */
        return $this->document;
    }

    /**
     * @phpstan-param ?TDocument $document
     */
    public function setDocument(?AbstractMainDocument $document): void
    {
        /** @var TDocument $document */
        $this->document = $document;
    }
}
