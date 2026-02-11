<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Twig\Runtime;

use League\CommonMark\Output\RenderedContentInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Shared\Domain\Content\Markdown\MarkdownConverter;
use Shared\Twig\Runtime\MarkdownExtensionRuntime;

class MarkdownExtensionRuntimeTest extends MockeryTestCase
{
    private MarkdownConverter&MockInterface $converter;
    private MarkdownExtensionRuntime $runtime;

    protected function setUp(): void
    {
        $this->converter = Mockery::mock(MarkdownConverter::class);

        $this->runtime = new MarkdownExtensionRuntime(
            $this->converter,
        );
    }

    public function testRenderMarkdown(): void
    {
        $input = 'foo';
        $renderedContent = Mockery::mock(RenderedContentInterface::class);
        $renderedContent->expects('getContent')->andReturn($expectedOutput = 'bar');
        $this->converter->expects('convert')->with($input)->andReturn($renderedContent);

        $this->assertSame(
            $expectedOutput,
            $this->runtime->renderMarkdown($input),
        );
    }

    public function testRenderMarkdownWithNullInput(): void
    {
        $input = null;
        $renderedContent = Mockery::mock(RenderedContentInterface::class);
        $renderedContent->shouldNotReceive('getContent');
        $this->converter->shouldNotReceive('convert');

        $this->assertNull($this->runtime->renderMarkdown($input));
    }
}
