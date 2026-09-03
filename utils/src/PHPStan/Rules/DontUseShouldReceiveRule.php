<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rules;

use Mockery\MockInterface;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;

use function array_key_exists;
use function in_array;
use function sprintf;

class DontUseShouldReceiveRule implements Rule
{
    /**
     * @var array<string,string> forbidden Mockery method => suggested alternative
     */
    private const array FORBIDDEN_METHODS = [
        'shouldReceive' => 'expects',
        'shouldNotReceive' => 'expects()->never()',
    ];

    public function getNodeType(): string
    {
        return Node\Expr\MethodCall::class;
    }

    /**
     * @throws ShouldNotHappenException
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! $node instanceof Node\Expr\MethodCall || ! $node->name instanceof Node\Identifier) {
            return [];
        }

        $methodName = $node->name->toString();

        if (! array_key_exists($methodName, self::FORBIDDEN_METHODS)) {
            return [];
        }

        $type = $scope->getType($node->var);

        if (! in_array(MockInterface::class, $type->getReferencedClasses())) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf(
                'Usage of %s is forbidden. Use %s instead.',
                $methodName,
                self::FORBIDDEN_METHODS[$methodName],
            ))
                ->identifier('woo.mockery.' . $methodName)
                ->build(),
        ];
    }
}
