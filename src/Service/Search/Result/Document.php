<?php

declare(strict_types=1);

namespace App\Service\Search\Result;

use App\ViewModel\DocumentSearchEntry;
use App\ViewModel\DossierReference;

class Document implements ResultEntry
{
    public function __construct(
        private readonly DocumentSearchEntry $document,
        /** @var DossierReference[] */
        private readonly array $dossiers,
        /** @var string[] */
        private readonly array $highlights,
    ) {
    }

    public function getType(): string
    {
        return ResultEntry::TYPE_DOCUMENT;
    }

    public function getDocument(): DocumentSearchEntry
    {
        return $this->document;
    }

    /**
     * @return string[]
     */
    public function getHighlights(): array
    {
        return $this->highlights;
    }

    /**
     * @return DossierReference[]
     */
    public function getDossiers(): array
    {
        return $this->dossiers;
    }
}
