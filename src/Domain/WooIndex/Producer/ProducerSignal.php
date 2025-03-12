<?php

declare(strict_types=1);

namespace App\Domain\WooIndex\Producer;

enum ProducerSignal
{
    case STOP_CHUNK;
}
