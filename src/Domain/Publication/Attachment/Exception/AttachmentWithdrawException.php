<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Attachment\Exception;

use RuntimeException;

class AttachmentWithdrawException extends RuntimeException implements AttachmentExceptionInterface
{
    public static function forCannotWithdraw(): self
    {
        return new self('Cannot cannot withdraw attachment');
    }
}
