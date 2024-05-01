<?php

declare(strict_types=1);

namespace App\Domain\Search\Result;

use App\Domain\Search\Index\ElasticDocumentType;
use App\ViewModel\CovenantSearchEntry;
use App\ViewModel\DossierSearchEntry;

class MainTypeEntry implements ResultEntryInterface
{
    public function __construct(
        private readonly ElasticDocumentType $type,
        private readonly DossierSearchEntry|CovenantSearchEntry $dossier,
        /** @var string[] */
        private readonly array $highlights,
    ) {
    }

    public function getType(): ElasticDocumentType
    {
        return $this->type;
    }

    public function getDossier(): DossierSearchEntry|CovenantSearchEntry
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
