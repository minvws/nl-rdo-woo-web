<?php

declare(strict_types=1);

namespace Shared\Twig\Extension;

use Shared\Twig\Runtime\MarkdownExtensionRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class MarkdownExtension extends AbstractExtension
{
    public function __construct(private readonly MarkdownExtensionRuntime $runtime)
    {
    }

    #[\Override]
    public function getFilters(): array
    {
        return [
            new TwigFilter('render_markdown', $this->runtime->renderMarkdown(...), [
                'is_safe' => ['html'],
            ]),
        ];
    }
}
