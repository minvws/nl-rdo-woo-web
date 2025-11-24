<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Domain\Content\Markdown;

use Shared\Domain\Content\Markdown\MarkdownConverter;
use Shared\Tests\Integration\SharedWebTestCase;

final class MarkdownConverterTest extends SharedWebTestCase
{
    private MarkdownConverter $markdownConverter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->markdownConverter = self::getContainer()->get(MarkdownConverter::class);
    }

    public function testCustomStrongRenderer(): void
    {
        $output = $this->markdownConverter->convert('**This is bold text**')->getContent();

        $this->assertStringContainsString('<span class="font-bold">This is bold text</span>', $output);
    }

    public function testCustomEmphasisRenderer(): void
    {
        $output = $this->markdownConverter->convert('_This is emphasized text_')->getContent();

        $this->assertStringContainsString('<span class="italic">This is emphasized text</span>', $output);
    }

    public function testCustomHeadingRenderer(): void
    {
        $output = $this->markdownConverter->convert('# Level 1 heading turned into level 2')->getContent();

        $this->assertStringContainsString('<h2>Level 1 heading turned into level 2</h2>', $output);
    }

    public function testFoobar(): void
    {
        $output = $this->markdownConverter->convert($this->getExampleMarkdownInput())->getContent();

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
