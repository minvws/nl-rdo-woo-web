<?php

declare(strict_types=1);

namespace Shared\Domain\WooIndex\Producer;

use Carbon\CarbonInterface;
use Shared\Domain\WooIndex\Builder\Changefreq;

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
