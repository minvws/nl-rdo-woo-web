<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\MainDocument;

/**
 * @template TDocument of AbstractMainDocument
 *
 * @property ?TDocument $document
 */
trait HasMainDocument
{
    /**
     * @return ?TDocument
     */
    public function getMainDocument(): ?AbstractMainDocument
    {
        return $this->document;
    }

    /**
     * @param ?TDocument $document
     */
    public function setMainDocument(?AbstractMainDocument $document): void
    {
        $this->document = $document;
    }
}
