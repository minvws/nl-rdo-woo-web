<?php

declare(strict_types=1);

namespace App\Domain\Search\Result\FacetValue;

interface FacetValueInterface
{
    public function __toString(): string;

    public function getValue(): string;

    public function getDescription(): string;
}
