<?php

declare(strict_types=1);

namespace Utils\Rector;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Property;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IntersectionTypeNode;
use PHPStan\PhpDocParser\Ast\Type\NullableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Rector\Comments\NodeDocBlock\DocBlockUpdater;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
use Webmozart\Assert\Assert;

use function array_unshift;
use function count;
use function property_exists;
use function strtolower;

/**
 * Transforms array<ValueType> → array<array-key, ValueType> in docblocks,
 * including array types nested inside array shapes and union/intersection types.
 *
 * Handles:
 *   array<string>                          → array<array-key, string>
 *   array<string, array<int>>              → array<string, array<array-key, int>>
 *   array{key: array<string>}              → array{key: array<array-key, string>}
 *   array<string>|null                     → array<array-key, string>|null
 *   array<array{nested: array<string>}>    → fully recursive
 */
final class AddArrayKeyToGenericArrayTypeRector extends AbstractRector
{
    public function __construct(
        private readonly PhpDocInfoFactory $phpDocInfoFactory,
        private readonly DocBlockUpdater $docBlockUpdater,
    ) {
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Adds explicit array-key to single-argument generic array types in docblocks, including inside array shapes',
            [
                new CodeSample(
                    <<<'PHP'
                    /**
                     * @var array<string, array{a: array<string>}> $myArray
                     */
                    $myArray = [];
                    PHP,
                    <<<'PHP'
                    /**
                     * @var array<string, array{a: array<array-key, string>}> $myArray
                     */
                    $myArray = [];
                    PHP,
                ),
            ],
        );
    }

    /**
     * @return array<array-key, class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [
            ClassMethod::class,
            Function_::class,
            Property::class,
            Expression::class, // covers inline /** @var */ on variables
        ];
    }

    public function refactor(Node $node): ?Node
    {
        $phpDocInfo = $this->phpDocInfoFactory->createFromNode($node);
        if ($phpDocInfo === null) {
            return null;
        }

        $changed = $this->rewritePhpDocNode($phpDocInfo->getPhpDocNode());
        if (! $changed) {
            return null;
        }

        $this->docBlockUpdater->updateRefactoredNodeWithPhpDocInfo($node);

        return $node;
    }

    private function rewritePhpDocNode(PhpDocNode $phpDocNode): bool
    {
        $changed = false;
        foreach ($phpDocNode->getTags() as $tag) {
            if (! property_exists($tag->value, 'type')) {
                continue;
            }

            $type = $tag->value->type;
            Assert::isInstanceOf($type, TypeNode::class);

            if ($this->rewriteTypeNode($type)) {
                $changed = true;
            }
        }

        return $changed;
    }

    /**
     * Recursively walks any TypeNode tree and rewrites array<T> → array<array-key, T>.
     * Covers GenericTypeNode, ArrayShapeNode, UnionTypeNode,
     * IntersectionTypeNode, and NullableTypeNode.
     */
    private function rewriteTypeNode(TypeNode $typeNode): bool
    {
        $changed = false;

        if ($typeNode instanceof GenericTypeNode) {
            // Fix single-arg array<T> at this level first.
            if (
                strtolower($typeNode->type->name) === 'array'
                && count($typeNode->genericTypes) === 1
            ) {
                array_unshift($typeNode->genericTypes, new IdentifierTypeNode('array-key'));
                $changed = true;
            }
            // Recurse into all generic args (value type may itself contain array<T>).
            foreach ($typeNode->genericTypes as $genericType) {
                if ($this->rewriteTypeNode($genericType)) {
                    $changed = true;
                }
            }

            return $changed;
        }

        if ($typeNode instanceof ArrayShapeNode) {
            // array{key: array<string>, ...} — recurse into each item's value type.
            foreach ($typeNode->items as $item) {
                if ($this->rewriteTypeNode($item->valueType)) {
                    $changed = true;
                }
            }

            return $changed;
        }

        if ($typeNode instanceof UnionTypeNode || $typeNode instanceof IntersectionTypeNode) {
            // array<string>|null, array<string>&Traversable, etc.
            foreach ($typeNode->types as $type) {
                if ($this->rewriteTypeNode($type)) {
                    $changed = true;
                }
            }

            return $changed;
        }

        if ($typeNode instanceof NullableTypeNode) {
            // ?array<string>
            return $this->rewriteTypeNode($typeNode->type);
        }

        return false;
    }
}
