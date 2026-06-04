<?php

declare(strict_types=1);

namespace Shared\Domain\Upload\FileType;

enum MimeTypeHelperResult
{
    case VALID;
    case INVALID_MIME_TYPE;
    case INVALID_EXTENSION;
    case MISMATCH_BETWEEN_EXTENSION_AND_MIME_TYPE;

    /**
     * @phpstan-assert-if-true self::VALID $this
     *
     * @phpstan-assert-if-false !self::VALID $this
     */
    public function isValid(): bool
    {
        return $this === self::VALID;
    }

    /**
     * @phpstan-assert-if-true !self::VALID $this
     *
     * @phpstan-assert-if-false self::VALID $this
     */
    public function isInvalid(): bool
    {
        return $this !== self::VALID;
    }
}
