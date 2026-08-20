<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Subject;

use function array_map;

final readonly class SubjectContentNode
{
    /**
     * @param list<SubjectContentNode> $children
     */
    public function __construct(
        public string $title,
        public string $body,
        /** @var list<SubjectContentNode> */
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
}
