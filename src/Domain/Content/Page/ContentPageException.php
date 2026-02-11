<?php

declare(strict_types=1);

namespace Shared\Domain\Content\Page;

use RuntimeException;

use function sprintf;

class ContentPageException extends RuntimeException
{
    public static function forMissing(ContentPageType $type): self
    {
        return new self(sprintf('Missing content page for type "%s"', $type->value));
    }
}
