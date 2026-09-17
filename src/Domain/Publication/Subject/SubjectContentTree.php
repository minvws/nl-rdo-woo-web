<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Subject;

use Shared\Domain\Publication\Subject\Constraint\ValidContentTreeDepth;
use Symfony\Component\Validator\Constraints as SymfonyAssert;
use Webmozart\Assert\Assert;

use function array_map;

final readonly class SubjectContentTree
{
    /**
     * @param list<SubjectContentNode> $children
     */
    public function __construct(
        #[SymfonyAssert\Length(max: 200)]
        public string $title,
        #[SymfonyAssert\Length(max: 10000)]
        public string $intro,
        /** @var list<SubjectContentNode> */
        #[SymfonyAssert\All([new SymfonyAssert\Type(SubjectContentNode::class)])]
        #[SymfonyAssert\Valid]
        #[ValidContentTreeDepth(max: 3)]
        public array $children,
        #[SymfonyAssert\Length(max: 10000)]
        public string $outro,
    ) {
    }

    /**
     * @return array{title: string, intro: string, children: list<array<string, mixed>>, outro: string}
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'intro' => $this->intro,
            'children' => array_map(static fn (SubjectContentNode $child): array => $child->toArray(), $this->children),
            'outro' => $this->outro,
        ];
    }

    /**
     * @param array<array-key, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $title = $data['title'] ?? '';
        $intro = $data['intro'] ?? '';
        $outro = $data['outro'] ?? '';
        Assert::string($title);
        Assert::string($intro);
        Assert::string($outro);

        $children = $data['children'] ?? [];
        Assert::isList($children);

        return new self(
            children: array_map(
                static function (mixed $child): SubjectContentNode {
                    Assert::isArray($child);

                    return SubjectContentNode::fromArray($child);
                },
                $children,
            ),
            title: $title,
            intro: $intro,
            outro: $outro,
        );
    }
}
