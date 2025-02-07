<?php

declare(strict_types=1);

namespace App\Domain\Publication\MainDocument;

/**
 * @template TDocument of AbstractMainDocument
 *
 * @property TDocument $document
 */
interface EntityWithMainDocument
{
    /**
     * @return class-string<TDocument>
     */
    public function getMainDocumentEntityClass(): string;

    /**
     * @return ?TDocument
     */
    public function getMainDocument(): ?AbstractMainDocument;

    /**
     * @param ?TDocument $document
     */
    public function setMainDocument(?AbstractMainDocument $document): void;
}
