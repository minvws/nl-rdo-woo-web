<?php

declare(strict_types=1);

namespace App\Service\Search\Result;

use App\Entity\Document;

class Page implements ResultEntry
{
    protected Document $document;
    protected int $pageNumber;

    /** @var string[] */
    protected array $highlights;
    /** @var mixed[] */
    protected array $elasticData;

    /**
     * @param string[] $highlights
     * @param mixed[]  $elasticData
     */
    public function __construct(Document $document, int $pageNumber, array $highlights, array $elasticData)
    {
        $this->document = $document;
        $this->highlights = $highlights;
        $this->elasticData = $elasticData;
        $this->pageNumber = $pageNumber;
    }

    public function getType(): string
    {
        return ResultEntry::TYPE_PAGE;
    }

    public function getDocument(): Document
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
