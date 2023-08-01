<?php

declare(strict_types=1);

namespace App\Service\Search\Result;

use App\Entity\Document as BaseDocument;

class Document implements ResultEntry
{
    protected BaseDocument $document;

    /** @var string[] */
    protected array $highlights;
    /** @var mixed[] */
    protected array $elasticData;

    /**
     * @param string[] $highlights
     * @param mixed[]  $elasticData
     */
    public function __construct(BaseDocument $document, array $highlights, array $elasticData)
    {
        $this->document = $document;
        $this->highlights = $highlights;
        $this->elasticData = $elasticData;
    }

    public function getType(): string
    {
        return ResultEntry::TYPE_DOCUMENT;
    }

    public function getDocument(): BaseDocument
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
     * @return mixed[]
     */
    public function getElastic(): array
    {
        return $this->elasticData;
    }
}
