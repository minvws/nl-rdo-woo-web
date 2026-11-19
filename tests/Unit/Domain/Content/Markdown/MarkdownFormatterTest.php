<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Content\Markdown;

use Shared\Domain\Content\Markdown\MarkdownFormatter;
use Shared\Domain\Content\Markdown\MarkdownFormatterException;
use Shared\Tests\Unit\UnitTestCase;

use function sprintf;

final class MarkdownFormatterTest extends UnitTestCase
{
    public function testText(): void
    {
        $plainText = $this->getFaker()->sentence();

        $markdownFormatter = new MarkdownFormatter();

        self::assertSame($plainText, $markdownFormatter->toPlainText($plainText));
    }

    public function testEmptyInput(): void
    {
        $markdownFormatter = new MarkdownFormatter();

        self::assertSame('', $markdownFormatter->toPlainText(''));
    }

    public function testWithEmphasisAndStrong(): void
    {
        $emphasised = $this->getFaker()->word();
        $strong = $this->getFaker()->word();

        $markdownFormatter = new MarkdownFormatter();

        self::assertSame(
            sprintf('%s %s', $emphasised, $strong),
            $markdownFormatter->toPlainText(sprintf('*%s* **%s**', $emphasised, $strong)),
        );
    }

    public function testLink(): void
    {
        $linkText = $this->getFaker()->word();
        $url = $this->getFaker()->url();

        $markdownFormatter = new MarkdownFormatter();

        self::assertSame($linkText, $markdownFormatter->toPlainText(sprintf('[%s](%s)', $linkText, $url)));
    }

    public function testWithUrl(): void
    {
        $linkText = $this->getFaker()->word();
        $url = $this->getFaker()->url();

        $markdownFormatter = new MarkdownFormatter();

        self::assertSame($linkText, $markdownFormatter->toPlainText(sprintf('**[*%s*](%s)**', $linkText, $url)));
    }

    public function testWithNewlines(): void
    {
        $firstItem = $this->getFaker()->word();
        $secondItem = $this->getFaker()->word();

        $markdownFormatter = new MarkdownFormatter();

        self::assertSame(
            sprintf('%s %s', $firstItem, $secondItem),
            $markdownFormatter->toPlainText(sprintf("- %s\n- %s", $firstItem, $secondItem)),
        );
    }

    public function testWithParagraphs(): void
    {
        $firstLine = $this->getFaker()->word();
        $secondLine = $this->getFaker()->word();
        $paragraph = $this->getFaker()->word();

        $markdownFormatter = new MarkdownFormatter();

        self::assertSame(
            sprintf('%s %s %s', $firstLine, $secondLine, $paragraph),
            $markdownFormatter->toPlainText(sprintf("%s\n%s\n\n%s", $firstLine, $secondLine, $paragraph)),
        );
    }

    public function testParseWithInvalidEncoding(): void
    {
        $markdownFormatter = new MarkdownFormatter();

        $this->expectException(MarkdownFormatterException::class);

        $markdownFormatter->parse("\xB1\x31");
    }

    public function testToHtmlWithInvalidEncoding(): void
    {
        $markdownFormatter = new MarkdownFormatter();

        $this->expectException(MarkdownFormatterException::class);

        $markdownFormatter->toHtml("\xB1\x31");
    }

    public function testToPlainTextWithInvalidEncoding(): void
    {
        $markdownFormatter = new MarkdownFormatter();

        $this->expectException(MarkdownFormatterException::class);

        $markdownFormatter->toPlainText("\xB1\x31");
    }
}
