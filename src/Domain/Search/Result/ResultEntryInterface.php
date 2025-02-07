<?php

declare(strict_types=1);

namespace App\Domain\Search\Result;

use App\Domain\Search\Index\ElasticDocumentType;

interface ResultEntryInterface
{
    public function getType(): ElasticDocumentType;

    /** @return string[] */
    public function getHighlights(): array;
}
