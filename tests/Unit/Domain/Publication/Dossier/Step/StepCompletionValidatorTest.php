<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Step;

use App\Domain\Publication\Dossier\Step\StepCompletionValidator;
use App\Domain\Publication\Dossier\Step\StepDefinitionInterface;
use App\Domain\Publication\Dossier\Step\StepName;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class StepCompletionValidatorTest extends MockeryTestCase
{
    public function testIsCompletedReturnsFalseWhenTheValidatorReturnsErrors(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);

        $step = \Mockery::mock(StepDefinitionInterface::class);
        $step->shouldReceive('getName')->andReturn(StepName::DECISION);

        $violations = \Mockery::mock(ConstraintViolationListInterface::class);
        $violations->expects('count')->andReturn(0);

        $validator = \Mockery::mock(ValidatorInterface::class);
        $validator->expects('validate')->with($dossier, null, StepName::DECISION->value)->andReturn($violations);

        $validator = new StepCompletionValidator($validator);

        self::assertTrue($validator->isCompleted($step, $dossier));
    }

    public function testIsCompletedReturnsTrueWhenTheValidatorReturnsErrors(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);

        $step = \Mockery::mock(StepDefinitionInterface::class);
        $step->shouldReceive('getName')->andReturn(StepName::DECISION);

        $violations = \Mockery::mock(ConstraintViolationListInterface::class);
        $violations->expects('count')->andReturn(3);

        $validator = \Mockery::mock(ValidatorInterface::class);
        $validator->expects('validate')->with($dossier, null, StepName::DECISION->value)->andReturn($violations);

        $validator = new StepCompletionValidator($validator);

        self::assertFalse($validator->isCompleted($step, $dossier));
    }
}
