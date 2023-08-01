<?php

declare(strict_types=1);

namespace App\Service\Search\Result;

use App\Entity\Dossier as BaseDossier;

class Dossier implements ResultEntry
{
    protected BaseDossier $dossier;

    /** @var string[] */
    protected array $highlights;
    /** @var mixed[] */
    protected array $elasticData;

    /**
     * @param string[] $highlights
     * @param mixed[]  $elasticData
     */
    public function __construct(BaseDossier $dossier, array $highlights, array $elasticData)
    {
        $this->dossier = $dossier;
        $this->highlights = $highlights;
        $this->elasticData = $elasticData;
    }

    public function getType(): string
    {
        return ResultEntry::TYPE_DOSSIER;
    }

    public function getDossier(): BaseDossier
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

    /**
     * @return mixed[]
     */
    public function getElastic(): array
    {
        return $this->elasticData;
    }
}
