<?php

declare(strict_types=1);

namespace App\Domain\Publication\Attachment\Exception;

class AttachmentWithdrawException extends \RuntimeException implements AttachmentExceptionInterface
{
    public static function forCannotWithdraw(): self
    {
        return new self('Cannot cannot withdraw attachment');
    }
}
