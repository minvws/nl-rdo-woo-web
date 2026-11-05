<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Subject;

use PHPUnit\Framework\Attributes\DataProvider;
use Shared\Domain\Publication\Subject\SubjectContentNode;
use Shared\Domain\Publication\Subject\SubjectContentTree;
use Shared\Tests\Unit\UnitTestCase;

final class SubjectContentTreeTest extends UnitTestCase
{
    public function testEqualToReturnsTrueForSameStructure(): void
    {
        self::assertTrue(self::createTree()->equalTo(self::createTree()));
    }

    public function testEqualToReturnsTrueForTreeRestoredFromArray(): void
    {
        $tree = self::createTree();

        self::assertTrue($tree->equalTo(SubjectContentTree::fromArray($tree->toArray())));
    }

    #[DataProvider('differentTreeDataProvider')]
    public function testEqualToReturnsFalseForDifferentTree(SubjectContentTree $other): void
    {
        self::assertFalse(self::createTree()->equalTo($other));
    }

    /**
     * @return array<string, array{other: SubjectContentTree}>
     */
    public static function differentTreeDataProvider(): array
    {
        return [
            'different title' => ['other' => self::createTree(title: 'Other title')],
            'different intro' => ['other' => self::createTree(intro: 'Other intro')],
            'different outro' => ['other' => self::createTree(outro: 'Other outro')],
            'different children order' => ['other' => self::createTree(children: [
                new SubjectContentNode('Second', 'Second body'),
                new SubjectContentNode('First', 'First body'),
            ])],
            'different nested child' => ['other' => self::createTree(children: [
                new SubjectContentNode('First', 'First body', [new SubjectContentNode('Nested', 'Nested body')]),
                new SubjectContentNode('Second', 'Second body'),
            ])],
            'no children' => ['other' => self::createTree(children: [])],
        ];
    }

    #[DataProvider('otherTypeDataProvider')]
    public function testEqualToReturnsFalseForOtherTypes(mixed $other): void
    {
        self::assertFalse(self::createTree()->equalTo($other));
    }

    /**
     * @return array<string, array{other: mixed}>
     */
    public static function otherTypeDataProvider(): array
    {
        return [
            'null' => ['other' => null],
            'array with same structure' => ['other' => self::createTree()->toArray()],
            'content node' => ['other' => new SubjectContentNode('First', 'First body')],
        ];
    }

    /**
     * @param list<SubjectContentNode>|null $children
     */
    private static function createTree(
        string $title = 'Title',
        string $intro = 'Intro',
        ?array $children = null,
        string $outro = 'Outro',
    ): SubjectContentTree {
        return new SubjectContentTree(
            title: $title,
            intro: $intro,
            children: $children ?? [
                new SubjectContentNode('First', 'First body'),
                new SubjectContentNode('Second', 'Second body'),
            ],
            outro: $outro,
        );
    }
}
