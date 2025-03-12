<?php

declare(strict_types=1);

namespace App\Domain\WooIndex\Producer;

use App\Domain\WooIndex\Changefreq;
use Carbon\CarbonInterface;

final readonly class Url
{
    public function __construct(
        public string $loc,
        public CarbonInterface $lastmod,
        public DiWooDocument $diWooDocument,
        public ?Changefreq $changefreq = null,
        public ?float $priority = null,
    ) {
    }
}
