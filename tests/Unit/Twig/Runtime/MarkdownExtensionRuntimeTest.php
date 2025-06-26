<?php

declare(strict_types=1);

namespace App\Tests\Unit\Twig\Runtime;

use App\Domain\Content\Markdown\MarkdownConverter;
use App\Twig\Runtime\MarkdownExtensionRuntime;
use League\CommonMark\Output\RenderedContentInterface;
use Mockery;
use Mockery\MockInterface;

class MarkdownExtensionRuntimeTest extends Mockery\Adapter\Phpunit\MockeryTestCase
{
    private MarkdownConverter&MockInterface $converter;
    private MarkdownExtensionRuntime $runtime;

    public function setUp(): void
    {
        $this->converter = \Mockery::mock(MarkdownConverter::class);

        $this->runtime = new MarkdownExtensionRuntime(
            $this->converter,
        );
    }

    public function testRenderMarkdown(): void
    {
        $input = 'foo';
        $renderedContent = \Mockery::mock(RenderedContentInterface::class);
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
        $renderedContent = \Mockery::mock(RenderedContentInterface::class);
        $renderedContent->shouldNotReceive('getContent');
        $this->converter->shouldNotReceive('convert');

        $this->assertNull($this->runtime->renderMarkdown($input));
    }
}
