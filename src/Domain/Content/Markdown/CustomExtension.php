<?php

declare(strict_types=1);

namespace Shared\Domain\Content\Markdown;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\CommonMark\Delimiter\Processor\EmphasisDelimiterProcessor;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Extension\CommonMark\Node\Block\ListBlock;
use League\CommonMark\Extension\CommonMark\Node\Block\ListItem;
use League\CommonMark\Extension\CommonMark\Node\Inline\Emphasis;
use League\CommonMark\Extension\CommonMark\Node\Inline\HtmlInline;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Extension\CommonMark\Node\Inline\Strong;
use League\CommonMark\Extension\CommonMark\Parser\Block\HeadingStartParser;
use League\CommonMark\Extension\CommonMark\Parser\Block\ListBlockStartParser;
use League\CommonMark\Extension\CommonMark\Parser\Inline\CloseBracketParser;
use League\CommonMark\Extension\CommonMark\Parser\Inline\HtmlInlineParser;
use League\CommonMark\Extension\CommonMark\Parser\Inline\OpenBracketParser;
use League\CommonMark\Extension\CommonMark\Renderer\Block\ListBlockRenderer;
use League\CommonMark\Extension\CommonMark\Renderer\Block\ListItemRenderer;
use League\CommonMark\Extension\CommonMark\Renderer\Inline\HtmlInlineRenderer;
use League\CommonMark\Extension\CommonMark\Renderer\Inline\LinkRenderer;
use League\CommonMark\Extension\ConfigurableExtensionInterface;
use League\CommonMark\Node\Block\Document;
use League\CommonMark\Node\Block\Paragraph;
use League\CommonMark\Node\Inline\Newline;
use League\CommonMark\Node\Inline\Text;
use League\CommonMark\Parser\Inline\NewlineParser;
use League\CommonMark\Renderer\Block\DocumentRenderer;
use League\CommonMark\Renderer\Block\ParagraphRenderer;
use League\CommonMark\Renderer\Inline\NewlineRenderer;
use League\CommonMark\Renderer\Inline\TextRenderer;
use League\Config\ConfigurationBuilderInterface;
use Nette\Schema\Expect;
use Shared\Domain\Content\Markdown\Renderer\CustomEmphasisRenderer;
use Shared\Domain\Content\Markdown\Renderer\CustomHeadingRenderer;
use Shared\Domain\Content\Markdown\Renderer\CustomStrongRenderer;

final class CustomExtension implements ConfigurableExtensionInterface
{
    public function configureSchema(ConfigurationBuilderInterface $builder): void
    {
        $builder->addSchema('commonmark', Expect::structure([
            'use_asterisk' => Expect::bool(true),
            'use_underscore' => Expect::bool(true),
            'enable_strong' => Expect::bool(true),
            'enable_em' => Expect::bool(true),
            'unordered_list_markers' => Expect::listOf('string')->min(1)->default(['*', '+', '-'])->mergeDefaults(false),
        ]));
    }

    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment
            ->addBlockStartParser(new HeadingStartParser(), 60)
            ->addBlockStartParser(new ListBlockStartParser(), 10) // custom

            ->addInlineParser(new NewlineParser(), 200)
            ->addInlineParser(new HtmlInlineParser(), 40)
            ->addInlineParser(new CloseBracketParser(), 30)
            ->addInlineParser(new OpenBracketParser(), 20)

            ->addRenderer(Document::class, new DocumentRenderer(), 0)
            ->addRenderer(Paragraph::class, new ParagraphRenderer(), 0)

            ->addRenderer(Heading::class, new CustomHeadingRenderer(), 0)

            ->addRenderer(ListBlock::class, new ListBlockRenderer(), 0)
            ->addRenderer(ListItem::class, new ListItemRenderer(), 0)

            ->addRenderer(Emphasis::class, new CustomEmphasisRenderer(), 0)
            ->addRenderer(HtmlInline::class, new HtmlInlineRenderer(), 0)
            ->addRenderer(Link::class, new LinkRenderer(), 0)
            ->addRenderer(Newline::class, new NewlineRenderer(), 0)
            ->addRenderer(Strong::class, new CustomStrongRenderer(), 0)
            ->addRenderer(Text::class, new TextRenderer(), 0);

        $environment->addDelimiterProcessor(new EmphasisDelimiterProcessor('*'));
        $environment->addDelimiterProcessor(new EmphasisDelimiterProcessor('_'));
    }
}
