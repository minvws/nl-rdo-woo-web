<?php

declare(strict_types=1);

namespace App\Twig\Runtime;

use App\Domain\Content\Markdown\MarkdownConverter;
use Twig\Extension\RuntimeExtensionInterface;

class MarkdownExtensionRuntime implements RuntimeExtensionInterface
{
    public function __construct(private MarkdownConverter $markdownConverter)
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
