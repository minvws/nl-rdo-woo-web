<?php

declare(strict_types=1);

namespace App\Domain\Publication\Attachment;

final class AttachmentTypeBranchException extends \RuntimeException implements AttachmentExceptionInterface
{
    public static function mandatoryArguments(): self
    {
        return new self('Setting a branch or non-empty array of attachmentTypes is required');
    }
}
