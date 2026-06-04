<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Property;
use PHPStan\Analyser\Scope;
use PHPStan\PhpDocParser\Ast\AbstractNodeVisitor;
use PHPStan\PhpDocParser\Ast\NodeTraverser;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

use function count;
use function in_array;
use function sprintf;
use function strtolower;

/**
 * Enforces that generic array types in PHPDoc always use the two-argument form:
 *   array<array-key, Foo>  (required)
 *   array<Foo>             (disallowed - key type is implicit)
 *
 * This is a style rule; PHPStan's type system treats both as identical.
 * The point is explicitness: you can see at a glance whether the array
 * can have string keys, int keys, or both.
 *
 * @implements Rule<Node>
 */
final readonly class RequireExplicitArrayKeyTypeRule implements Rule
{
    /** Lowercase identifiers treated as "array" generics that must have explicit keys. */
    public const array ARRAY_TYPE_IDENTIFIERS = ['array'];

    /**
     * Identifiers that are already fully-keyed by definition and must NOT be touched:
     *   - list<T>      => always int keys, 0-based
     *   - non-empty-array<T> with one arg is still ambiguous, so we flag it too
     */
    public const array SKIP_IDENTIFIERS = ['list', 'non-empty-list'];

    public function __construct(
        private Lexer $phpDocLexer,
        private PhpDocParser $phpDocParser,
    ) {
    }

    public function getNodeType(): string
    {
        // We register on the base Node so one rule covers methods, functions,
        // closures, arrow functions, and properties. The processNode body
        // short-circuits immediately for nodes without doc comments, so the
        // performance overhead is negligible.
        return Node::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        // Only node types that can carry a PHPDoc are worth inspecting.
        if (
            ! $node instanceof ClassMethod
            && ! $node instanceof Function_
            && ! $node instanceof Closure
            && ! $node instanceof ArrowFunction
            && ! $node instanceof Property
        ) {
            return [];
        }

        $docComment = $node->getDocComment();
        if ($docComment === null) {
            return [];
        }

        $tokens = new TokenIterator($this->phpDocLexer->tokenize($docComment->getText()));
        $phpDocNode = $this->phpDocParser->parse($tokens);

        // Collect all offending GenericTypeNodes via a visitor.
        $offenders = [];

        $visitor = new class($offenders) extends AbstractNodeVisitor {
            /**
             * @param list<GenericTypeNode> $offenders
             *
             * @phpstan-ignore property.onlyWritten
             */
            public function __construct(private array &$offenders)
            {
            }

            public function enterNode(\PHPStan\PhpDocParser\Ast\Node $node): \PHPStan\PhpDocParser\Ast\Node
            {
                if (! $node instanceof GenericTypeNode) {
                    return $node;
                }

                $name = strtolower($node->type->name);

                // Skip types that are already explicit by design (list, non-empty-list).
                if (in_array($name, RequireExplicitArrayKeyTypeRule::SKIP_IDENTIFIERS, true)) {
                    return $node;
                }

                // Flag single-argument array<T> — the two-argument form is required.
                if (
                    in_array($name, RequireExplicitArrayKeyTypeRule::ARRAY_TYPE_IDENTIFIERS, true)
                    && count($node->genericTypes) === 1
                ) {
                    $this->offenders[] = $node;
                }

                return $node;
            }
        };

        $traverser = new NodeTraverser([$visitor]);
        $traverser->traverse([$phpDocNode]);

        if ($offenders === []) {
            return [];
        }

        $errors = [];
        foreach ($offenders as $offender) {
            $errors[] = RuleErrorBuilder::message(
                sprintf(
                    'PHPDoc type "%s" is missing an explicit key type. For example use array<array-key, %s>.',
                    (string) $offender,
                    (string) $offender->genericTypes[0],
                ),
            )
                ->line($docComment->getStartLine())
                ->identifier('phpDoc.arrayMissingKeyType')
                ->build();
        }

        return $errors;
    }
}
