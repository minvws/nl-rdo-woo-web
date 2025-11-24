<?php

declare(strict_types=1);

namespace Shared\Domain\S3;

enum StreamMode: string
{
    case READ_ONLY = 'rb';
    case WRITE_ONLY = 'wb';
}
