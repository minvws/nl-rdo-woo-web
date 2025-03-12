<?php

declare(strict_types=1);

namespace App\Domain\WooIndex;

use Symfony\Component\Finder\Finder;

readonly class WooIndexFinderFactory
{
    public function create(string $path): Finder
    {
        return Finder::create()
            ->in($path)
            ->depth(0)
            ->directories()
            ->sortByName()
            ->reverseSorting()
            ->name(WooIndexNamer::RUN_ID_REGEX);
    }
}
