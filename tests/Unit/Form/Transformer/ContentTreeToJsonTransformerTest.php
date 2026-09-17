<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Form\Transformer;

use Shared\Domain\Publication\Subject\SubjectContentNode;
use Shared\Domain\Publication\Subject\SubjectContentTree;
use Shared\Form\Transformer\ContentTreeToJsonTransformer;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;

class ContentTreeToJsonTransformerTest extends UnitTestCase
{
    private ContentTreeToJsonTransformer $transformer;

    protected function setUp(): void
    {
        $this->transformer = new ContentTreeToJsonTransformer();
    }

    public function testTransformReturnsEmptyStringWhenValueIsNull(): void
    {
        $this->assertSame('', $this->transformer->transform(null));
    }

    public function testTransformReturnsEmptyStringWhenValueIsAnEmptyContentTree(): void
    {
        $this->assertSame('', $this->transformer->transform(new SubjectContentTree(title: '', intro: '', children: [], outro: '')));
    }

    public function testTransformReturnsPrettyPrintedJson(): void
    {
        $result = $this->transformer->transform(new SubjectContentTree(
            title: '',
            intro: '',
            children: [new SubjectContentNode('Node', 'Body')],
            outro: '',
        ));

        $this->assertJsonStringEqualsJsonString(
            '{"title":"","intro":"","outro":"","children":[{"title":"Node","body":"Body","children":[]}]}',
            $result,
        );
        $this->assertStringContainsString("\n", $result);
    }

    public function testTransformDoesNotEscapeSlashesAndUnicode(): void
    {
        $result = $this->transformer->transform(new SubjectContentTree(
            title: 'Vergunningen',
            intro: 'Zie https://example.org/foo',
            children: [],
            outro: '',
        ));

        $this->assertStringContainsString('https://example.org/foo', $result);
        $this->assertStringContainsString('Vergunningen', $result);
    }

    public function testTransformThrowsExceptionForInvalidType(): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessageIs('Expected Shared\Domain\Publication\Subject\SubjectContentTree, got int');

        $this->transformer->transform(42);
    }

    public function testReverseTransformReturnsNullWhenValueIsNull(): void
    {
        $this->assertNull($this->transformer->reverseTransform(null));
    }

    public function testReverseTransformReturnsNullForBlankString(): void
    {
        $this->assertNull($this->transformer->reverseTransform("  \n "));
    }

    public function testReverseTransformReturnsDecodedContentTree(): void
    {
        $result = $this->transformer->reverseTransform(
            '{"title":"T","intro":"I","outro":"O","children":[{"title":"Node","body":"Body",'
                . '"children":[{"title":"Child","body":"","children":[]}]}]}',
        );

        $this->assertEquals(
            new SubjectContentTree(
                children: [
                    new SubjectContentNode('Node', 'Body', [
                        new SubjectContentNode('Child', ''),
                    ]),
                ],
                title: 'T',
                intro: 'I',
                outro: 'O',
            ),
            $result,
        );
    }

    public function testReverseTransformThrowsExceptionForNonStringValue(): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessageIs('Expected string, got int');

        $this->transformer->reverseTransform(123);
    }

    public function testReverseTransformSetsTranslationKeyForInvalidJson(): void
    {
        try {
            $this->transformer->reverseTransform('{ not json');
            $this->fail('Expected TransformationFailedException to be thrown');
        } catch (TransformationFailedException $e) {
            $this->assertSame('subject_landing_page_content_tree_invalid_json', $e->getInvalidMessage());
        }
    }

    public function testReverseTransformSetsTranslationKeyWhenJsonIsAList(): void
    {
        try {
            $this->transformer->reverseTransform('[{"title":"Node"}]');
            $this->fail('Expected TransformationFailedException to be thrown');
        } catch (TransformationFailedException $e) {
            $this->assertSame('subject_landing_page_content_tree_invalid_structure', $e->getInvalidMessage());
        }
    }

    public function testReverseTransformSetsTranslationKeyWhenAnItemIsNotAnArray(): void
    {
        try {
            $this->transformer->reverseTransform('{"children":["just a string"]}');
            $this->fail('Expected TransformationFailedException to be thrown');
        } catch (TransformationFailedException $e) {
            $this->assertSame('subject_landing_page_content_tree_invalid_structure', $e->getInvalidMessage());
        }
    }
}
