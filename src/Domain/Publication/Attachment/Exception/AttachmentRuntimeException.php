<?php

declare(strict_types=1);

namespace App\Domain\Publication\Attachment\Exception;

use App\Domain\Publication\Attachment\AbstractAttachment;

final class AttachmentRuntimeException extends \RuntimeException implements AttachmentExceptionInterface
{
    /**
     * @param class-string<AbstractAttachment> $classString
     */
    public static function unknownAttachmentType(string $classString): self
    {
        return new self(sprintf('Unknown attachment type: "%s"', $classString));
    }
}
