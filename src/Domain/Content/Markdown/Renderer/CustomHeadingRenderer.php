<?php

declare(strict_types=1);

namespace Shared\Domain\Content\Markdown\Renderer;

use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;

final readonly class CustomHeadingRenderer implements NodeRendererInterface
{
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): HtmlElement
    {
        /** @var Heading $node */
        Heading::assertInstanceOf($node);

        $level = $node->getLevel();

        $tag = $level === 1 ? 'h2' : 'h' . $level;

        /** @var array<string,string> $attrs */
        $attrs = $node->data->get('attributes');

        return new HtmlElement($tag, $attrs, $childRenderer->renderNodes($node->children()));
    }
}
