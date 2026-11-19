<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Domain\Content\Markdown;

use Shared\Domain\Content\Markdown\MarkdownFormatter;
use Shared\Tests\Integration\SharedWebTestCase;

final class MarkdownFormatterTest extends SharedWebTestCase
{
    private MarkdownFormatter $markdownFormatter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->markdownFormatter = self::fromContainer(MarkdownFormatter::class);
    }

    public function testCustomStrongRenderer(): void
    {
        $output = $this->markdownFormatter->toHtml('**This is bold text**');

        $this->assertStringContainsString('<span class="font-bold">This is bold text</span>', $output);
    }

    public function testCustomEmphasisRenderer(): void
    {
        $output = $this->markdownFormatter->toHtml('_This is emphasized text_');

        $this->assertStringContainsString('<span class="italic">This is emphasized text</span>', $output);
    }

    public function testCustomHeadingRenderer(): void
    {
        $output = $this->markdownFormatter->toHtml('# Level 1 heading turned into level 2');

        $this->assertStringContainsString('<h2>Level 1 heading turned into level 2</h2>', $output);
    }

    public function testExampleMarkdownRenderer(): void
    {
        $output = $this->markdownFormatter->toHtml($this->getExampleMarkdownInput());

        $this->assertMatchesHtmlSnapshot($output);
    }

    private function getExampleMarkdownInput(): string
    {
        return <<<'MD'
            **strong Hello World**

            _Emphasized Hello World_

            A link to [exmaple.org](https://example.org)

            - Banana
            - Pineapple
            - Apple

            1. First
            2. Second
            3. Third

            https://example.org

            \n\\n<br>

            <span>Foobar</span>

            # Header 1

            ## Header 2

            ### Header 3

            ![Alt text](https://dev.w3.org/SVG/tools/svgweb/samples/svg-files/cc.svg "a title")

            Another `foobar` one.

            ```
            foobar
            ```
            MD;
    }
}
