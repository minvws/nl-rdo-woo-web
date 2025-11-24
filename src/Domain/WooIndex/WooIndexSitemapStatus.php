<?php

declare(strict_types=1);

namespace Shared\Domain\WooIndex;

enum WooIndexSitemapStatus: string
{
    case PROCESSING = 'processing';
    case DONE = 'done';
}
