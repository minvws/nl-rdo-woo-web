<?php

declare(strict_types=1);

namespace Shared\Domain\Content\Markdown;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Exception\CommonMarkException;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Extension\DefaultAttributes\DefaultAttributesExtension;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use League\CommonMark\Node\Block\AbstractBlock;
use League\CommonMark\Node\Block\Document;
use League\CommonMark\Node\Inline\Newline;
use League\CommonMark\Node\StringContainerInterface;
use League\CommonMark\Parser\MarkdownParser;
use League\CommonMark\Parser\MarkdownParserInterface;
use League\CommonMark\Renderer\DocumentRendererInterface;
use League\CommonMark\Renderer\HtmlRenderer;
use League\CommonMark\Util\HtmlFilter;

use function array_filter;
use function implode;

readonly class MarkdownFormatter
{
    private MarkdownParserInterface $parser;
    private DocumentRendererInterface $renderer;

    public function __construct()
    {
        $environment = new Environment([
            'html_input' => HtmlFilter::STRIP,
            'allow_unsafe_links' => false,
            'max_nesting_level' => 10,
            'max_delimiters_per_line' => 1_000,
            'default_attributes' => [
                Link::class => [
                    'target' => '_blank',
                ],
            ],
        ]);

        $environment->addExtension(new CustomExtension());
        $environment->addExtension(new ExternalLinkExtension());
        $environment->addExtension(new DefaultAttributesExtension());

        $this->parser = new MarkdownParser($environment);
        $this->renderer = new HtmlRenderer($environment);
    }

    /**
     * @throws MarkdownFormatterException
     */
    public function toHtml(string $markdown): string
    {
        $documentAST = $this->parse($markdown);

        return $this->renderer->renderDocument($documentAST)->getContent();
    }

    /**
     * @throws MarkdownFormatterException
     */
    public function toPlainText(string $markdown): string
    {
        $content = $this->parse($markdown);

        $segments = [];
        $literals = [];
        foreach ($content->iterator() as $node) {
            if ($node instanceof StringContainerInterface) {
                $literals[] = $node->getLiteral();

                continue;
            }

            if ($node instanceof AbstractBlock || $node instanceof Newline) {
                $segments[] = implode('', $literals);
                $literals = [];
            }
        }

        $segments[] = implode('', $literals);
        $segments = array_filter($segments, static function (string $segment): bool {
            return $segment !== '';
        });

        return implode(' ', $segments);
    }

    /**
     * @throws MarkdownFormatterException
     */
    public function parse(string $markdown): Document
    {
        try {
            return $this->parser->parse($markdown);
        } catch (CommonMarkException $commonMarkException) {
            throw MarkdownFormatterException::fromCommonMarkException($commonMarkException);
        }
    }
}
