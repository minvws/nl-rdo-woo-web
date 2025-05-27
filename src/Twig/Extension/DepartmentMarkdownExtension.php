<?php

declare(strict_types=1);

namespace App\Twig\Extension;

use App\Twig\Runtime\DepartmentMarkdownExtensionRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class DepartmentMarkdownExtension extends AbstractExtension
{
    public function __construct(private DepartmentMarkdownExtensionRuntime $runtime)
    {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('as_markdown_for_department', $this->runtime->asMarkdown(...), [
                'is_safe' => ['html'],
                'pre_escape' => 'html',
            ]),
        ];
    }
}
