<?php

declare(strict_types=1);

namespace App\Domain\Search\Result;

use App\Domain\Search\Index\ElasticDocumentType;
use App\ViewModel\DocumentSearchEntry;
use App\ViewModel\DossierReference;

class SubTypeEntry implements ResultEntryInterface
{
    public function __construct(
        private readonly DocumentSearchEntry $document,
        /** @var DossierReference[] */
        private readonly array $dossiers,
        /** @var string[] */
        private readonly array $highlights,
    ) {
    }

    public function getType(): ElasticDocumentType
    {
        return ElasticDocumentType::WOO_DECISION_DOCUMENT;
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
