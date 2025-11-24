<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\MainDocument\Exception;

use Shared\Domain\Publication\Attachment\Exception\AttachmentExceptionInterface;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;

final class MainDocumentRuntimeException extends \RuntimeException implements AttachmentExceptionInterface
{
    /**
     * @param class-string<AbstractMainDocument> $classString
     */
    public static function unknownMainDocumentType(string $classString): self
    {
        return new self(sprintf('Unknown main document type: "%s"', $classString));
    }
}
