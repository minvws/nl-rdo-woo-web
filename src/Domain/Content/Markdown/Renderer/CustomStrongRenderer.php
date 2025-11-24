<?php

declare(strict_types=1);

namespace Shared\Domain\Content\Markdown\Renderer;

use League\CommonMark\Extension\CommonMark\Node\Inline\Strong;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;

final readonly class CustomStrongRenderer implements NodeRendererInterface
{
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): HtmlElement
    {
        /** @var Strong $node */
        Strong::assertInstanceOf($node);

        /** @var array<string,string> $attrs */
        $attrs = $node->data->get('attributes');

        $attrs['class'] = trim(($attrs['class'] ?? '') . ' font-bold');

        return new HtmlElement('span', $attrs, $childRenderer->renderNodes($node->children()));
    }
}
