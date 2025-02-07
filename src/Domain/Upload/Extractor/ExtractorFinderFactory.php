<?php

declare(strict_types=1);

namespace App\Domain\Upload\Extractor;

use Symfony\Component\Finder\Finder;

readonly class ExtractorFinderFactory
{
    public function create(string $dir): Finder
    {
        return Finder::create()
            ->in($dir)
            ->files();
    }
}
