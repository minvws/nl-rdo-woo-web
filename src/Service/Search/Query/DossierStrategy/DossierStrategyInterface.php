<?php

declare(strict_types=1);

namespace App\Service\Search\Query\DossierStrategy;

use App\Service\Search\Model\Config;

interface DossierStrategyInterface
{
    public function getPath(string $field): string;

    public function mustTypeCheck(): bool;

    public function getMinimumShouldMatch(): int;

    /**
     * @return string[]
     */
    public function getStatusValues(Config $config): array;
}
