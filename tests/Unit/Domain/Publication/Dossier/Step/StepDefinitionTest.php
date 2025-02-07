<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Step;

use App\Domain\Publication\Dossier\Step\StepDefinition;
use App\Domain\Publication\Dossier\Step\StepException;
use App\Domain\Publication\Dossier\Step\StepName;
use App\Domain\Publication\Dossier\Type\DossierType;
use App\Domain\Publication\Dossier\Type\DossierTypeConfigInterface;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\WooDecision;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class StepDefinitionTest extends MockeryTestCase
{
    public function testGetters(): void
    {
        $step = new StepDefinition(StepName::DECISION, DossierType::WOO_DECISION);

        self::assertEquals(StepName::DECISION, $step->getName());
        self::assertEquals(DossierType::WOO_DECISION, $step->getDossierType());
        self::assertEquals('app_admin_dossier_woodecision_decision_concept', $step->getConceptEditRouteName());
        self::assertEquals('app_admin_dossier_woodecision_decision_edit', $step->getEditRouteName());
    }

    public function testIsCompletedReturnsTrueWhenTheValidatorReturnsNoErrors(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('getType')->andReturn(DossierType::WOO_DECISION);

        $violations = \Mockery::mock(ConstraintViolationListInterface::class);
        $violations->expects('count')->andReturn(0);

        $validator = \Mockery::mock(ValidatorInterface::class);
        $validator->expects('validate')->with($dossier, null, [StepName::DECISION->value])->andReturn($violations);

        $step = new StepDefinition(StepName::DECISION, DossierType::WOO_DECISION);

        self::assertTrue($step->isCompleted($dossier, $validator));
    }

    public function testIsCompletedReturnsFalseWhenTheValidatorReturnsErrors(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('getType')->andReturn(DossierType::WOO_DECISION);

        $violations = \Mockery::mock(ConstraintViolationListInterface::class);
        $violations->expects('count')->andReturn(2);

        $validator = \Mockery::mock(ValidatorInterface::class);
        $validator->expects('validate')->with($dossier, null, [StepName::DECISION->value])->andReturn($violations);

        $step = new StepDefinition(StepName::DECISION, DossierType::WOO_DECISION);

        self::assertFalse($step->isCompleted($dossier, $validator));
    }

    public function testIsCompletedThrowsExceptionForTypeMismatch(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('getType')->andReturn(DossierType::WOO_DECISION);

        $step = new StepDefinition(StepName::DECISION, DossierType::COVENANT);

        $validator = \Mockery::mock(ValidatorInterface::class);

        $this->expectException(StepException::class);
        $step->isCompleted($dossier, $validator);
    }

    public function testCreate(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('getType')->andReturn(DossierType::WOO_DECISION);

        $violations = \Mockery::mock(ConstraintViolationListInterface::class);
        $violations->expects('count')->andReturn(0);

        $validator = \Mockery::mock(ValidatorInterface::class);
        $validator->expects('validate')->with($dossier, null, [StepName::DECISION->value])->andReturn($violations);

        $config = \Mockery::mock(DossierTypeConfigInterface::class);
        $config->shouldReceive('getDossierType')->andReturn(DossierType::WOO_DECISION);

        $step = StepDefinition::create($config, StepName::DECISION);

        self::assertTrue($step->isCompleted($dossier, $validator));
    }
}
