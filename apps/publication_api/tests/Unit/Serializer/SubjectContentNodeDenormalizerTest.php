<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Serializer;

use PublicationApi\Serializer\SubjectContentNodeDenormalizer;
use Shared\Domain\Publication\Subject\SubjectContentNode;
use Shared\Tests\Unit\UnitTestCase;
use stdClass;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;

final class SubjectContentNodeDenormalizerTest extends UnitTestCase
{
    private SubjectContentNodeDenormalizer $denormalizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->denormalizer = new SubjectContentNodeDenormalizer();
    }

    public function testDenormalizesValidNode(): void
    {
        $result = $this->denormalizer->denormalize(
            ['title' => 'T', 'body' => 'B', 'children' => []],
            SubjectContentNode::class,
        );

        self::assertInstanceOf(SubjectContentNode::class, $result);
        self::assertSame('T', $result->title);
        self::assertSame('B', $result->body);
        self::assertSame([], $result->children);
    }

    public function testDenormalizesNestedChildren(): void
    {
        $result = $this->denormalizer->denormalize(
            [
                'title' => 'Parent',
                'body' => 'PB',
                'children' => [
                    ['title' => 'Child', 'body' => 'CB', 'children' => []],
                ],
            ],
            SubjectContentNode::class,
        );

        self::assertCount(1, $result->children);
        self::assertSame('Child', $result->children[0]->title);
    }

    public function testThrowsWhenChildrenKeyIsAbsent(): void
    {
        $this->expectException(NotNormalizableValueException::class);

        $this->denormalizer->denormalize(
            ['title' => 'T', 'body' => 'B'],
            SubjectContentNode::class,
        );
    }

    public function testThrowsWhenChildrenIsNull(): void
    {
        $this->expectException(NotNormalizableValueException::class);

        $this->denormalizer->denormalize(
            ['title' => 'T', 'body' => 'B', 'children' => null],
            SubjectContentNode::class,
        );
    }

    public function testThrowsWhenChildrenIsAssocArray(): void
    {
        $this->expectException(NotNormalizableValueException::class);

        $this->denormalizer->denormalize(
            ['title' => 'T', 'body' => 'B', 'children' => ['key' => 'value']],
            SubjectContentNode::class,
        );
    }

    public function testThrowsWhenDataIsNotArray(): void
    {
        $this->expectException(NotNormalizableValueException::class);

        $this->denormalizer->denormalize('invalid', SubjectContentNode::class);
    }

    public function testThrowsWhenTitleIsMissing(): void
    {
        $this->expectException(NotNormalizableValueException::class);

        $this->denormalizer->denormalize(
            ['body' => 'B', 'children' => []],
            SubjectContentNode::class,
        );
    }

    public function testThrowsWhenBodyIsMissing(): void
    {
        $this->expectException(NotNormalizableValueException::class);

        $this->denormalizer->denormalize(
            ['title' => 'T', 'children' => []],
            SubjectContentNode::class,
        );
    }

    public function testErrorPathIncludesChildrenWhenAbsent(): void
    {
        try {
            $this->denormalizer->denormalize(
                ['title' => 'T', 'body' => 'B'],
                SubjectContentNode::class,
                null,
                ['deserialization_path' => 'root'],
            );
            self::fail('Expected NotNormalizableValueException');
        } catch (NotNormalizableValueException $e) {
            $path = $e->getPath();
            self::assertNotNull($path);
            self::assertStringContainsString('root.children', $path);
        }
    }

    public function testSupportsDenormalization(): void
    {
        self::assertTrue($this->denormalizer->supportsDenormalization([], SubjectContentNode::class));
        self::assertFalse($this->denormalizer->supportsDenormalization([], stdClass::class));
    }
}
