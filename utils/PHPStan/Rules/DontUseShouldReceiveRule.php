<?php

declare(strict_types=1);

namespace Woo\Stan\Rules;

use Mockery\MockInterface;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;

class DontUseShouldReceiveRule implements Rule
{
    public function getNodeType(): string
    {
        return Node\Expr\MethodCall::class;
    }

    /**
     * @throws ShouldNotHappenException
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($node instanceof Node\Expr\MethodCall && $node->name instanceof Node\Identifier && $node->name->toString() === 'shouldReceive') {
            $type = $scope->getType($node->var);

            if (in_array(MockInterface::class, $type->getReferencedClasses())) {
                return [
                    RuleErrorBuilder::message('Usage of shouldReceive is forbidden. Use expects instead.')
                        ->identifier('woo.mockery.shouldReceive')
                        ->build(),
                ];
            }
        }

        return [];
    }
}
