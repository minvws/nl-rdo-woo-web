<?php

declare(strict_types=1);

namespace App\Domain\Search\Result\SubType;

use App\Domain\Publication\Dossier\Type\DossierReference;
use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Result\ResultEntryInterface;

class SubTypeSearchResultEntry implements ResultEntryInterface
{
    public function __construct(
        private readonly SubTypeViewModelInterface $viewModel,
        /** @var DossierReference[] */
        private readonly array $dossiers,
        /** @var string[] */
        private readonly array $highlights,
        private readonly ElasticDocumentType $type,
    ) {
    }

    public function getType(): ElasticDocumentType
    {
        return $this->type;
    }

    public function getViewModel(): SubTypeViewModelInterface
    {
        return $this->viewModel;
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
