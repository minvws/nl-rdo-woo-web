<?php

declare(strict_types=1);

namespace App\Domain\Publication\Attachment\Exception;

final class AttachmentNotFoundException extends \RuntimeException implements AttachmentExceptionInterface
{
    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct('Attachment not found', previous: $previous);
    }
}
