<?php

declare(strict_types=1);

namespace Shared\Domain\WooIndex\Producer;

use Carbon\CarbonInterface;
use Shared\Domain\WooIndex\Builder\Changefreq;
use Shared\ValueObject\Url as UrlValueObject;

final readonly class Url
{
    public function __construct(
        public UrlValueObject $loc,
        public CarbonInterface $lastmod,
        public DiWooDocument $diWooDocument,
        public ?Changefreq $changefreq = null,
        public ?float $priority = null,
    ) {
    }
}
