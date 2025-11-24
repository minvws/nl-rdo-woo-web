<?php

declare(strict_types=1);

namespace Shared\Domain\WooIndex\Producer;

enum ProducerSignal
{
    case STOP_CHUNK;
}
