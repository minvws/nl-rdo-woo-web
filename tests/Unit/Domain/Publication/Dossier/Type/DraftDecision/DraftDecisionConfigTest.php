<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Type\DraftDecision;

use Mockery;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecisionConfig;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Workflow\WorkflowInterface;

final class DraftDecisionConfigTest extends UnitTestCase
{
    public function testSecurityExpressionIsNullWhenFeatureIsEnabled(): void
    {
        $config = new DraftDecisionConfig(
            Mockery::mock(WorkflowInterface::class),
            hasFeatureDraftDecision: true,
        );

        $expression = $config->getSecurityExpression();

        self::assertNull($expression);
    }

    public function testSecurityExpressionIsDisabledWhenFeatureIsDisabled(): void
    {
        $config = new DraftDecisionConfig(
            Mockery::mock(WorkflowInterface::class),
            hasFeatureDraftDecision: false,
        );

        $expression = $config->getSecurityExpression();

        self::assertInstanceOf(Expression::class, $expression);
        self::assertSame('false', (string) $expression);
    }
}
