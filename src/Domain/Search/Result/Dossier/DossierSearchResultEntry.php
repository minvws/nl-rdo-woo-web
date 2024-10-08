<?php

declare(strict_types=1);

namespace App\Domain\Search\Result\Dossier;

use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Result\ResultEntryInterface;

class DossierSearchResultEntry implements ResultEntryInterface
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
