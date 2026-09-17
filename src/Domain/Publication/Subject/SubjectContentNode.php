<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Subject;

use League\CommonMark\Extension\CommonMark\Node\Block\BlockQuote;
use League\CommonMark\Extension\CommonMark\Node\Block\ListBlock;
use League\CommonMark\Extension\CommonMark\Node\Block\ListItem;
use League\CommonMark\Extension\CommonMark\Node\Inline\Emphasis;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Extension\CommonMark\Node\Inline\Strong;
use League\CommonMark\Node\Block\Document;
use League\CommonMark\Node\Block\Paragraph;
use League\CommonMark\Node\Inline\Newline;
use League\CommonMark\Node\Inline\Text;
use Shared\Validator\MarkdownAllowedNodeTypes\MarkdownAllowedNodeTypes;
use Symfony\Component\Validator\Constraints as SymfonyAssert;
use Webmozart\Assert\Assert;

use function array_map;

final readonly class SubjectContentNode
{
    /**
     * @param list<SubjectContentNode> $children
     */
    public function __construct(
        public string $title,
        #[MarkdownAllowedNodeTypes(allowedNodeTypes: [
            Paragraph::class, ListBlock::class, BlockQuote::class, Document::class,
            Text::class, Newline::class, ListBlock::class, ListItem::class,
            Emphasis::class, Strong::class, Link::class,
        ])]
        public string $body,
        /** @var list<SubjectContentNode> */
        #[SymfonyAssert\Valid]
        public array $children = [],
    ) {
    }

    /**
     * Normalize the node to a plain array for JSON storage.
     *
     * @return array{title: string, body: string, children: list<array<string, mixed>>}
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'children' => array_map(static fn (SubjectContentNode $child): array => $child->toArray(), $this->children),
        ];
    }

    /**
     * @param array<array-key, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        Assert::keyExists($data, 'title');
        Assert::keyExists($data, 'body');
        Assert::string($data['title']);
        Assert::string($data['body']);

        $children = $data['children'] ?? [];
        Assert::isList($children);

        return new self(
            title: $data['title'],
            body: $data['body'],
            children: array_map(
                static function (mixed $child): self {
                    Assert::isArray($child);

                    return self::fromArray($child);
                },
                $children,
            ),
        );
    }
}
