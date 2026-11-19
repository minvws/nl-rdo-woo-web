<?php

declare(strict_types=1);

namespace Shared\Twig\Runtime;

use Shared\Domain\Content\Markdown\MarkdownFormatter;
use Twig\Extension\RuntimeExtensionInterface;

readonly class MarkdownExtensionRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private MarkdownFormatter $markdownFormatter,
    ) {
    }

    public function renderMarkdown(?string $input): ?string
    {
        if ($input === null) {
            return null;
        }

        return $this->markdownFormatter->toHtml($input);
    }
}
