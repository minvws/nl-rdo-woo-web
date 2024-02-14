<?php

declare(strict_types=1);

namespace App\Service\Search\Result;

use App\ViewModel\DossierSearchEntry;

class Dossier implements ResultEntry
{
    public function __construct(
        private readonly DossierSearchEntry $dossier,
        /** @var string[] */
        private readonly array $highlights,
    ) {
    }

    public function getType(): string
    {
        return ResultEntry::TYPE_DOSSIER;
    }

    public function getDossier(): DossierSearchEntry
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
