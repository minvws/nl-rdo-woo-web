<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Attachment\Exception;

use RuntimeException;

final class AttachmentTypeBranchException extends RuntimeException implements AttachmentExceptionInterface
{
    public static function mandatoryArguments(): self
    {
        return new self('Setting a branch or non-empty array of attachmentTypes is required');
    }
}
