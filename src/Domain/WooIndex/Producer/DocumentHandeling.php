<?php

declare(strict_types=1);

namespace Shared\Domain\WooIndex\Producer;

use Carbon\CarbonInterface;
use Shared\Domain\WooIndex\Tooi\SoortHandeling;

final readonly class DocumentHandeling
{
    public function __construct(
        public SoortHandeling $soortHandeling,
        public CarbonInterface $atTime,
    ) {
    }
}
