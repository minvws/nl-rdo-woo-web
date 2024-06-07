<?php

declare(strict_types=1);

namespace App\Domain\Search\Result;

use App\Domain\Publication\Dossier\Type\WooDecision\ViewModel\DossierReference;
use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Result\WooDecision\DocumentSearchResult;

class SubTypeEntry implements ResultEntryInterface
{
    public function __construct(
        private readonly DocumentSearchResult $document,
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

    public function getDocument(): DocumentSearchResult
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
