<?php

declare(strict_types=1);

namespace Shared\Twig\Runtime;

use Shared\Domain\Content\Markdown\MarkdownConverter;
use Twig\Extension\RuntimeExtensionInterface;

class MarkdownExtensionRuntime implements RuntimeExtensionInterface
{
    public function __construct(private readonly MarkdownConverter $markdownConverter)
    {
    }

    public function renderMarkdown(?string $input): ?string
    {
        if ($input === null) {
            return null;
        }

        return $this->markdownConverter->convert($input)->getContent();
    }
}
