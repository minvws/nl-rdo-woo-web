<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Subject;

use Shared\Domain\Publication\Subject\SubjectContentNode;
use Shared\Tests\Unit\UnitTestCase;

final class SubjectContentNodeTest extends UnitTestCase
{
    public function testToArrayNormalizesNestedNodes(): void
    {
        $node = new SubjectContentNode(
            'Root title',
            'Root body',
            [
                new SubjectContentNode(
                    'Child title',
                    'Child body',
                    [
                        new SubjectContentNode('Grandchild title', 'Grandchild body'),
                    ],
                ),
            ],
        );

        self::assertSame(
            [
                'title' => 'Root title',
                'body' => 'Root body',
                'children' => [
                    [
                        'title' => 'Child title',
                        'body' => 'Child body',
                        'children' => [
                            [
                                'title' => 'Grandchild title',
                                'body' => 'Grandchild body',
                                'children' => [],
                            ],
                        ],
                    ],
                ],
            ],
            $node->toArray(),
        );
    }
}
