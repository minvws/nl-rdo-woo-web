<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Step;

use Mockery;
use Shared\Domain\Publication\Dossier\Step\StepDefinition;
use Shared\Domain\Publication\Dossier\Step\StepException;
use Shared\Domain\Publication\Dossier\Step\StepName;
use Shared\Domain\Publication\Dossier\Type\DossierType;
use Shared\Domain\Publication\Dossier\Type\DossierTypeConfigInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class StepDefinitionTest extends UnitTestCase
{
    public function testGetters(): void
    {
        $conceptRouteName = $this->getFaker()->word();
        $editRouteName = $this->getFaker()->word();

        $step = new StepDefinition(StepName::DECISION, DossierType::WOO_DECISION, $conceptRouteName, $editRouteName);

        self::assertEquals(StepName::DECISION, $step->getName());
        self::assertEquals(DossierType::WOO_DECISION, $step->getDossierType());
        self::assertSame($conceptRouteName, $step->getConceptEditRouteName());
        self::assertSame($editRouteName, $step->getEditRouteName());
    }

    public function testIsCompletedReturnsTrueWhenTheValidatorReturnsNoErrors(): void
    {
        $dossier = Mockery::mock(WooDecision::class);
        $dossier->expects('getType')->andReturn(DossierType::WOO_DECISION);

        $violations = Mockery::mock(ConstraintViolationListInterface::class);
        $violations->expects('count')->andReturn(0);

        $validator = Mockery::mock(ValidatorInterface::class);
        $validator->expects('validate')->with($dossier, null, [StepName::DECISION->value])->andReturn($violations);

        $step = new StepDefinition(StepName::DECISION, DossierType::WOO_DECISION, $this->getFaker()->word(), $this->getFaker()->word());

        self::assertTrue($step->isCompleted($dossier, $validator));
    }

    public function testIsCompletedReturnsFalseWhenTheValidatorReturnsErrors(): void
    {
        $dossier = Mockery::mock(WooDecision::class);
        $dossier->expects('getType')->andReturn(DossierType::WOO_DECISION);

        $violations = Mockery::mock(ConstraintViolationListInterface::class);
        $violations->expects('count')->andReturn(2);

        $validator = Mockery::mock(ValidatorInterface::class);
        $validator->expects('validate')->with($dossier, null, [StepName::DECISION->value])->andReturn($violations);

        $step = new StepDefinition(StepName::DECISION, DossierType::WOO_DECISION, $this->getFaker()->word(), $this->getFaker()->word());

        self::assertFalse($step->isCompleted($dossier, $validator));
    }

    public function testIsCompletedThrowsExceptionForTypeMismatch(): void
    {
        $dossier = Mockery::mock(WooDecision::class);
        $dossier->expects('getType')->andReturn(DossierType::WOO_DECISION);

        $step = new StepDefinition(StepName::DECISION, DossierType::COVENANT, $this->getFaker()->word(), $this->getFaker()->word());

        $validator = Mockery::mock(ValidatorInterface::class);

        $this->expectException(StepException::class);
        $step->isCompleted($dossier, $validator);
    }

    public function testCreate(): void
    {
        $dossier = Mockery::mock(WooDecision::class);
        $dossier->expects('getType')->andReturn(DossierType::WOO_DECISION);

        $violations = Mockery::mock(ConstraintViolationListInterface::class);
        $violations->expects('count')->andReturn(0);

        $validator = Mockery::mock(ValidatorInterface::class);
        $validator->expects('validate')->with($dossier, null, [StepName::DECISION->value])->andReturn($violations);

        $config = Mockery::mock(DossierTypeConfigInterface::class);
        $config->expects('getDossierType')->andReturn(DossierType::WOO_DECISION);

        $conceptRouteName = $this->getFaker()->word();
        $editRouteName = $this->getFaker()->word();

        $step = StepDefinition::create($config, StepName::DECISION, $conceptRouteName, $editRouteName);

        self::assertTrue($step->isCompleted($dossier, $validator));
        self::assertSame($conceptRouteName, $step->getConceptEditRouteName());
        self::assertSame($editRouteName, $step->getEditRouteName());
    }
}
