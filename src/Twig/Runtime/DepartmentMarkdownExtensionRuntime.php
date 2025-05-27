<?php

declare(strict_types=1);

namespace App\Twig\Runtime;

use App\Domain\Department\Markdown\MarkdownConverter;
use Twig\Extension\RuntimeExtensionInterface;

class DepartmentMarkdownExtensionRuntime implements RuntimeExtensionInterface
{
    public function __construct(private MarkdownConverter $markdownConverter)
    {
    }

    public function asMarkdown(string $input): string
    {
        return $this->markdownConverter->convert($input)->getContent();
    }
}
