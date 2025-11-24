<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Result\SubType;

use Shared\Domain\Publication\Dossier\Type\DossierReference;
use Shared\Domain\Search\Index\ElasticDocumentType;
use Shared\Domain\Search\Result\ResultEntryInterface;

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
