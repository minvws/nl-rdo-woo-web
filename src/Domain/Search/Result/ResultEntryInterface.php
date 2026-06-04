<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Result;

use Shared\Domain\Search\Index\ElasticDocumentType;

interface ResultEntryInterface
{
    public function getType(): ElasticDocumentType;

    /** @return array<array-key, string> */
    public function getHighlights(): array;
}
