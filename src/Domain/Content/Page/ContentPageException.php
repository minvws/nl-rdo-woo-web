<?php

declare(strict_types=1);

namespace App\Domain\Content\Page;

class ContentPageException extends \RuntimeException
{
    public static function forMissing(ContentPageType $type): self
    {
        return new self(sprintf('Missing content page for type "%s"', $type->value));
    }
}
