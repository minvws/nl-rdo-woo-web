<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Twig\Runtime;

use Mockery;
use Shared\Domain\Content\Markdown\MarkdownFormatter;
use Shared\Tests\Unit\UnitTestCase;
use Shared\Twig\Runtime\MarkdownExtensionRuntime;

class MarkdownExtensionRuntimeTest extends UnitTestCase
{
    public function testRenderMarkdown(): void
    {
        $markdown = $this->getFaker()->sentence();
        $html = $this->getFaker()->sentence();

        $markdownFormatter = Mockery::mock(MarkdownFormatter::class);
        $markdownFormatter->expects('toHtml')->with($markdown)->andReturn($html);

        $markdownExtensionRuntime = new MarkdownExtensionRuntime($markdownFormatter);

        self::assertSame($html, $markdownExtensionRuntime->renderMarkdown($markdown));
    }

    public function testRenderMarkdownWithNull(): void
    {
        $markdownExtensionRuntime = new MarkdownExtensionRuntime(Mockery::mock(MarkdownFormatter::class));

        self::assertNull($markdownExtensionRuntime->renderMarkdown(null));
    }
}
