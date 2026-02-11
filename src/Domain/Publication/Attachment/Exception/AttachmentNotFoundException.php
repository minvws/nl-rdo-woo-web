<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Attachment\Exception;

use RuntimeException;
use Throwable;

final class AttachmentNotFoundException extends RuntimeException implements AttachmentExceptionInterface
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('Attachment not found', previous: $previous);
    }
}
