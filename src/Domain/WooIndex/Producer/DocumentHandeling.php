<?php

declare(strict_types=1);

namespace App\Domain\WooIndex\Producer;

use App\Domain\WooIndex\Tooi\SoortHandeling;
use Carbon\CarbonInterface;

final readonly class DocumentHandeling
{
    public function __construct(
        public SoortHandeling $soortHandeling,
        public CarbonInterface $atTime,
    ) {
    }
}
