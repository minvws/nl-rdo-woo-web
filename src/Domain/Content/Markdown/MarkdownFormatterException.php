<?php

declare(strict_types=1);

namespace Shared\Domain\Content\Markdown;

use League\CommonMark\Exception\CommonMarkException;
use RuntimeException;

final class MarkdownFormatterException extends RuntimeException
{
    public static function fromCommonMarkException(CommonMarkException $commonMarkException): self
    {
        return new self($commonMarkException->getMessage(), 0, $commonMarkException);
    }
}
