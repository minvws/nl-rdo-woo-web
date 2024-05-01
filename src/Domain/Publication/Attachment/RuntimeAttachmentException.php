<?php

declare(strict_types=1);

namespace App\Domain\Publication\Attachment;

final class RuntimeAttachmentException extends \RuntimeException implements AttachmentExceptionInterface
{
    /**
     * @param class-string<AbstractAttachment> $classString
     */
    public static function unknownAttachmentType(string $classString): self
    {
        return new self(sprintf('Unknown attachment type: "%s"', $classString));
    }
}
