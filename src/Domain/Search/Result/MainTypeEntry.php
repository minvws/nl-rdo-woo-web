<?php

declare(strict_types=1);

namespace App\Domain\Search\Result;

use App\Domain\Search\Index\ElasticDocumentType;

class MainTypeEntry implements ResultEntryInterface
{
    public function __construct(
        private readonly ElasticDocumentType $type,
        private readonly DossierTypeSearchResultInterface $dossier,
        /** @var string[] */
        private readonly array $highlights,
    ) {
    }

    public function getType(): ElasticDocumentType
    {
        return $this->type;
    }

    public function getDossier(): DossierTypeSearchResultInterface
    {
        return $this->dossier;
    }

    /**
     * @return string[]
     */
    public function getHighlights(): array
    {
        return $this->highlights;
    }
}
