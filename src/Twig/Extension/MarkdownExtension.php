<?php

declare(strict_types=1);

namespace App\Twig\Extension;

use App\Twig\Runtime\MarkdownExtensionRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class MarkdownExtension extends AbstractExtension
{
    public function __construct(private readonly MarkdownExtensionRuntime $runtime)
    {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('render_markdown', $this->runtime->renderMarkdown(...), [
                'is_safe' => ['html'],
                'pre_escape' => 'html',
            ]),
        ];
    }
}
